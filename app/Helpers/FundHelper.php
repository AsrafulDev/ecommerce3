<?php

namespace App\Helpers;

use App\Models\FundTransaction;
use App\Models\Order;

class FundHelper
{
    public static function balance()
    {
        $in  = FundTransaction::where('direction', 'in')->sum('amount');
        $out = FundTransaction::where('direction', 'out')->sum('amount');
        return $in - $out;
    }

    /**
     * Total already credited to the fund for an order (source = 'sale').
     */
    public static function creditedFor(int $orderId): float
    {
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
