<?php

namespace App\Services;

use App\Helpers\FundHelper;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Services\Accounting\FullAccountingGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single place a payment against an order is recorded.
 *
 * Before this, "collect money" was re-implemented in six spots (POS, order edit,
 * the add-payment modal, four gateway callbacks) with subtly different results —
 * the gateway paths flipped payment_status to 'paid' while leaving paid_amount
 * and the fund ledger untouched, so real online money never reached the cash
 * book. Every collection now funnels through collect() so:
 *
 *   - paid_amount / due_amount / payment_status stay consistent, because they are
 *     derived from the order_payments ledger (Order::recalculatePaymentTotals),
 *   - the Lite fund gets its 'sale' cash credit (FundHelper, capped at order total
 *     so a replayed webhook can never over-credit),
 *   - and, when full accounting is on, one journal per collection is attempted.
 *
 * The amount is capped to the outstanding due, so collect() is safe to call more
 * than once for the same money: the second call finds nothing left to collect and
 * returns null. A known gateway transaction id is deduped as well.
 *
 * The accounting call runs AFTER the money is committed and is wrapped so a
 * ledger failure can never roll back — or throw into — a customer's payment.
 */
class PaymentCollectionService
{
    public function __construct(protected FullAccountingGateway $ledger) {}

    /**
     * Record a collection of $amount against $order.
     *
     * @param  string|null $trxRef  a gateway transaction id, used to dedup replays
     * @return OrderPayment|null the payment row, or null when nothing was collected
     */
    public function collect(
        Order $order,
        float $amount,
        string $method = 'Cash',
        ?string $trxRef = null,
        ?int $userId = null,
        ?string $note = null
    ): ?OrderPayment {
        $amount = round($amount, 2);

        // A replayed webhook carrying the same gateway transaction id has already
        // been collected — return the existing row rather than booking it twice.
        // Checked before the cap so the replay is recognised as the SAME event
        // (and can be re-posted idempotently) instead of looking like "nothing due".
        if ($trxRef !== null && $trxRef !== '') {
            if ($existing = OrderPayment::where('order_id', $order->id)
                    ->where('trx_note', $trxRef)
                    ->first()) {
                return $existing;
            }
        }

        // How much more can be collected, measured straight from the ledger
        // (not the due_amount column, which an order may not have initialised
        // yet at gateway time). This is what keeps a replay or an over-payment
        // from booking more than the order is worth.
        $collected = round((float) OrderPayment::where('order_id', $order->id)->sum('amount'), 2);
        $remaining = round((float) $order->amount - $collected, 2);
        $actual    = min($amount, $remaining);

        // Nothing outstanding, or a zero/negative payment: there is no money to
        // move. This is what makes a double-clicked or replayed gateway callback a
        // no-op instead of a phantom payment.
        if ($actual <= 0) {
            return null;
        }

        $payment = DB::transaction(function () use ($order, $actual, $method, $trxRef, $userId, $note) {
            $payment = OrderPayment::create([
                'order_id'       => $order->id,
                'customer_id'    => $order->customer_id,
                'amount'         => $actual,
                'payment_method' => $method,
                'trx_note'       => $trxRef ?: ($note ?: null),
                'created_by'     => $userId ?? ($order->updated_by ?? 1),
            ]);

            // Derive the order's totals from the ledger — the one source of truth
            // for paid/due/status, so no caller sets them by hand again.
            $order->refresh();
            $order->recalculatePaymentTotals();

            // Keep the flat payments row (current state) in step.
            $row               = Payment::where('order_id', $order->id)->firstOrNew(['order_id' => $order->id]);
            $row->customer_id  = $order->customer_id;
            $row->amount       = $order->paid_amount;
            $row->payment_status = $order->payment_status;
            if (!$row->payment_method) {
                $row->payment_method = $method;
            }
            $row->save();

            // Lite cash book: capped at order total minus what is already credited,
            // so this and the delivery-time creditSale can never combine to over-credit.
            FundHelper::creditPayment(
                $order,
                $actual,
                $note ?: 'Payment received — Order #' . ($order->invoice_id ?? $order->id)
            );

            return $payment;
        });

        // Post to the general ledger only after the money is safely committed, and
        // never let a posting problem bubble back into the payment.
        try {
            $this->ledger->paymentReceived($payment);
        } catch (Throwable $e) {
            Log::warning('Accounting posting failed for collected payment', [
                'order_payment_id' => $payment->id,
                'error'            => $e->getMessage(),
            ]);
        }

        return $payment;
    }
}
