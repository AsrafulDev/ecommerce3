<?php

namespace App\Helpers;

use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FundHelper
{
    /**
     * Realizable fund balance: cash the business actually holds.
     *
     * Derived from the SAME income() rule the dashboard uses, so the headline
     * balance and the income figures can never drift apart. 'sale' rows are
     * booked when an order is delivered (COD is credited in full even before the
     * courier remits), so their un-collected part is not cash and is removed.
     */
    public static function balance(): float
    {
        return round(self::income() - self::spend(), 2);
    }

    /**
     * Realizable cash-in. Optional [since, until) window bounds the rows and,
     * with them, the uncollected 'sale' portion removed — so income() over all
     * time is exactly the money that has actually been received.
     */
    public static function income(?Carbon $since = null, ?Carbon $until = null): float
    {
        $in = (float) self::bounded(
            FundTransaction::where('direction', 'in'), $since, $until
        )->sum('amount');

        // A legacy delivered-order sale row and a canonical payment row may
        // coexist while old orders are being collected. Once a canonical
        // payment exists, the payment rows are the cash source; hide the old
        // aggregate sale row from realizable income to prevent double-counting.
        $legacySaleReplaced = (float) self::bounded(
            FundTransaction::where('direction', 'in')
                ->where('source', 'sale')
                ->whereIn('source_id', OrderPayment::whereNotNull('fund_transaction_id')->select('order_id')),
            $since,
            $until
        )->sum('amount');

        return round($in - $legacySaleReplaced - self::uncollectedSaleCredits($since, $until), 2);
    }

    /**
     * Cash paid out. Optional [since, until) window. There is no accrual
     * subtlety here: an 'out' row is money that left.
     */
    public static function spend(?Carbon $since = null, ?Carbon $until = null): float
    {
        return round((float) self::bounded(
            FundTransaction::where('direction', 'out'), $since, $until
        )->sum('amount'), 2);
    }

    /**
     * Sum over unpaid orders of LEAST(sale credits, remaining due) — the portion
     * of credited sale money that has not actually been received. Scoped to the
     * same optional window as income() so a figure and its exclusion always
     * cover the same rows.
     */
    public static function uncollectedSaleCredits(?Carbon $since = null, ?Carbon $until = null): float
    {
        $credits = self::bounded(
            DB::table('fund_transactions')
                ->selectRaw('source_id, SUM(amount) as credited')
                ->where('source', 'sale')
                ->where('direction', 'in')
                ->whereNotNull('source_id'),
            $since,
            $until
        )->whereNotIn('source_id', OrderPayment::whereNotNull('fund_transaction_id')->select('order_id'))
            ->groupBy('source_id');

        return (float) DB::query()
            ->fromSub($credits, 'x')
            ->join('orders as o', 'o.id', '=', 'x.source_id')
            ->where('o.payment_status', '!=', 'paid')
            ->sum(DB::raw('LEAST(x.credited, GREATEST(0, o.due_amount))'));
    }

    /**
     * Apply a [since, until) window to any query whose rows carry created_at.
     * One definition of "in a period" shared by every figure above.
     */
    private static function bounded($query, ?Carbon $since, ?Carbon $until)
    {
        return $query
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('created_at', '<', $until));
    }

    /**
     * Total already credited to the fund for an order (source = 'sale').
     */
    public static function creditedFor(int $orderId): float
    {
        if (OrderPayment::where('order_id', $orderId)->whereNotNull('fund_transaction_id')->exists()) {
            return (float) (Order::find($orderId)?->amount ?? 0);
        }

        return (float) FundTransaction::where('source', 'sale')
            ->where('source_id', $orderId)
            ->sum('amount');
    }

    /**
     * ⭐ Reconcile an order's fund credit up to its FULL order amount.
     * Credits only the remaining delta, so orders that already received partial
     * payment credits (POS / addPayment) get topped up exactly once — never
     * double-credited, never skipped.
     *
     * @return bool true when a new fund row was created
     */
    public static function creditSale(Order $order, ?string $note = null, ?int $userId = null): bool
    {
        $remaining = round((float) $order->amount - self::creditedFor((int) $order->id), 2);

        return $remaining > 0
            ? self::credit((int) $order->id, $remaining, $note ?? 'Order complete (#' . ($order->invoice_id ?? $order->id) . ')', $userId)
            : false;
    }

    /**
     * ⭐ Credit an actual payment received for an order, capped so the lifetime
     * SUM of 'sale' rows for the order never exceeds order.amount.
     *
     * @return bool true when a new fund row was created
     */
    public static function creditPayment(Order $order, float $amount, ?string $note = null, ?int $userId = null): bool
    {
        $capped = round(min($amount, (float) $order->amount - self::creditedFor((int) $order->id)), 2);

        return $capped > 0
            ? self::credit((int) $order->id, $capped, $note ?? 'Payment received — Order #' . ($order->invoice_id ?? $order->id), $userId)
            : false;
    }

    /**
     * ⭐ Guarded refund debit keyed by refunds.id (source = 'refund').
     *
     * @return bool true when a new fund row was created
     */
    public static function debitRefund(int $sourceId, float $amount, ?string $note = null, ?int $userId = null): bool
    {
        if (FundTransaction::where('source', 'refund')->where('source_id', $sourceId)->exists()) {
            return false;
        }

        return self::debit('refund', $sourceId, $amount, $note, $userId);
    }

    /**
     * ⭐ Refund raised directly from an order's payment-status toggle.
     * Uses its own source ('order_refund') so it can never collide with
     * refunds.id-keyed rows, while still respecting legacy 'refund' rows
     * written for the same order id.
     *
     * @return bool true when a new fund row was created
     */
    public static function debitOrderRefund(Order $order, ?string $note = null, ?int $userId = null): bool
    {
        $id = (int) $order->id;

        $already = FundTransaction::where('source', 'order_refund')->where('source_id', $id)->exists()
            || FundTransaction::where('source', 'refund')->where('source_id', $id)->exists();

        if ($already) {
            return false;
        }

        return self::debit('order_refund', $id, round((float) $order->amount, 2), $note, $userId);
    }

    private static function credit(int $orderId, float $amount, ?string $note, ?int $userId): bool
    {
        $balanceBefore = self::balance();
        $createdBy     = $userId ?? (auth()->id() ?? 1);

        // balance_* are NOT in FundTransaction::$fillable → set them directly.
        $tx                 = new FundTransaction();
        $tx->direction      = 'in';
        $tx->source         = 'sale';
        $tx->source_id      = $orderId;
        $tx->amount         = $amount;
        $tx->note           = $note;
        $tx->created_by     = $createdBy;
        $tx->balance_before = $balanceBefore;
        $tx->balance_after  = $balanceBefore + $amount;
        $tx->save();

        return $tx->exists;
    }

    private static function debit(string $source, int $sourceId, float $amount, ?string $note, ?int $userId): bool
    {
        $balanceBefore = self::balance();
        $createdBy     = $userId ?? (auth()->id() ?? 1);

        $tx                 = new FundTransaction();
        $tx->direction      = 'out';
        $tx->source         = $source;
        $tx->source_id      = $sourceId;
        $tx->amount         = $amount;
        $tx->note           = $note;
        $tx->created_by     = $createdBy;
        $tx->balance_before = $balanceBefore;
        $tx->balance_after  = $balanceBefore - $amount;
        $tx->save();

        return $tx->exists;
    }
}
