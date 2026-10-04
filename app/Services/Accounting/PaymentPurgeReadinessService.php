<?php

namespace App\Services\Accounting;

use App\Models\OrderPayment;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;

/**
 * Read-only Phase 2B-0 audit. This service deliberately never authorizes or
 * deletes a payment; it explains why the future handler must remain blocked.
 */
class PaymentPurgeReadinessService
{
    public function __construct(private AdvancedAccountingGateway $accounting) {}

    public function customer(OrderPayment $payment): array
    {
        $payment->loadMissing(['order.refunds', 'customer']);
        $order = $payment->order;
        $blockers = ['PAYMENT_PURGE_NOT_ENABLED_PHASE_2B'];
        $funds = $order ? \App\Models\FundTransaction::where('source', 'sale')->where('source_id', $order->id)->get() : collect();
        $journal = $this->accounting->financialJournalState('customer_payment', (int) $payment->id);

        if (!$funds->count()) $blockers[] = 'MISSING_FUND_TRANSACTION';
        else $blockers[] = 'FUND_TRANSACTION_IS_ORDER_AGGREGATE';
        if (!$order) $blockers[] = 'MISSING_ORDER';
        if ($order && in_array((string) $order->order_status, ['cancelled', 'returned', 'return_approved', 'closed'], true)) $blockers[] = 'ORDER_CANCELLED_OR_RETURNED';
        if ($order && $order->refunds->isNotEmpty()) $blockers[] = 'DEPENDENT_REFUND_EXISTS';
        if ($journal === null && $this->accounting->enabled()) $blockers[] = 'MISSING_ADVANCED_JOURNAL';
        if (is_array($journal) && isset($journal['blocker'])) $blockers[] = $journal['blocker'];
        if (is_array($journal) && !isset($journal['blocker'])) $journalDependency = $journal; else $journalDependency = null;

        return [
            'status' => 'BLOCKED',
            'type' => 'customer_payment',
            'source' => $payment,
            'blockers' => array_values(array_unique($blockers)),
            'dependencies' => [
                'order' => $order ? ['id' => $order->id, 'status' => $order->order_status, 'amount' => $order->amount, 'paid_amount' => $order->paid_amount, 'due_amount' => $order->due_amount, 'payment_status' => $order->payment_status] : null,
                'customer' => $payment->customer ? ['id' => $payment->customer->id, 'name' => $payment->customer->name] : null,
                'fund_transactions' => $funds->map(fn ($fund) => ['id' => $fund->id, 'source' => $fund->source, 'source_id' => $fund->source_id, 'amount' => $fund->amount, 'direction' => $fund->direction])->all(),
                'advanced_journal' => $journalDependency,
                'refund_count' => $order?->refunds->count() ?? 0,
            ],
            'reconciliation' => [
                'due' => 'Order::recalculatePaymentTotals() can derive due/status from remaining OrderPayment rows.',
                'fund' => 'Not independently reversible because current Lite fund credit is order-aggregate, not payment-specific.',
                'ar' => 'Advanced CUSTOMER_PAYMENT journal is payment-specific when present; opening-AR settlement still needs explicit cutover proof.',
            ],
        ];
    }

    public function supplier(SupplierPayment $payment): array
    {
        $payment->loadMissing(['purchase.supplier', 'supplier', 'fundTransaction']);
        $purchase = $payment->purchase;
        $blockers = ['PAYMENT_PURGE_NOT_ENABLED_PHASE_2B', 'SUPPLIER_DUE_RECOMPUTATION_REQUIRED'];
        $journal = $this->accounting->financialJournalState('supplier_payment', (int) $payment->id);
        $returns = $purchase ? SupplierReturn::where('purchase_id', $purchase->id)->get(['id', 'return_no', 'status', 'total_amount']) : collect();

        if (!$payment->fund_transaction_id || !$payment->fundTransaction) $blockers[] = 'MISSING_FUND_TRANSACTION';
        elseif ($payment->fundTransaction->source !== 'supplier_payment' || (int) $payment->fundTransaction->source_id !== (int) $payment->id) $blockers[] = 'FUND_TRANSACTION_LINK_MISMATCH';
        if (!$purchase) $blockers[] = 'MISSING_PURCHASE';
        if ($returns->isNotEmpty()) $blockers[] = 'DEPENDENT_SUPPLIER_RETURN_EXISTS';
        if ($journal === null && $this->accounting->enabled()) $blockers[] = 'MISSING_ADVANCED_JOURNAL';
        if (is_array($journal) && isset($journal['blocker'])) $blockers[] = $journal['blocker'];
        if (is_array($journal) && !isset($journal['blocker'])) $journalDependency = $journal; else $journalDependency = null;

        return [
            'status' => 'BLOCKED',
            'type' => 'supplier_payment',
            'source' => $payment,
            'blockers' => array_values(array_unique($blockers)),
            'dependencies' => [
                'purchase' => $purchase ? ['id' => $purchase->id, 'supplier_id' => $purchase->supplier_id, 'grand_total' => $purchase->grand_total, 'paid_amount' => $purchase->paid_amount, 'due_amount' => $purchase->due_amount] : null,
                'supplier' => $payment->supplier ? ['id' => $payment->supplier->id, 'name' => $payment->supplier->name, 'current_due' => $payment->supplier->current_due] : null,
                'fund_transaction' => $payment->fundTransaction?->only(['id', 'source', 'source_id', 'amount', 'direction']),
                'advanced_journal' => $journalDependency,
                'supplier_returns' => $returns->map(fn ($return) => $return->toArray())->all(),
            ],
            'reconciliation' => [
                'due' => 'Purchase paid/due and Supplier current_due are persisted and need authoritative recomputation from purchase/payment history.',
                'fund' => 'The current payment flow has an explicit one-to-one fund_transaction_id when created through audited paths.',
                'ap' => 'SUPPLIER_PAYMENT is Dr AP / Cr default fund when the Advanced journal exists; opening-AP settlement needs explicit cutover proof.',
            ],
        ];
    }
}
