<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\FundTransaction;
use App\Enums\TransactionCategory;

/** Keeps supplier due columns derived from purchase/payment history. */
class SupplierPaymentReconciliationService
{
    public function recordTrace(Purchase $purchase, float $amount, string $date, ?string $note, ?int $userId): SupplierPayment
    {
        $fund = FundTransaction::create([
            'direction' => 'out',
            'source' => 'supplier_payment',
            'source_id' => null,
            'transaction_category' => TransactionCategory::SUPPLIER_PAYMENT,
            'amount' => $amount,
            'note' => $note ?: 'Supplier payment: ' . $purchase->invoice_no,
            'created_by' => $userId ?? 1,
        ]);

        $payment = SupplierPayment::create([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'amount' => $amount,
            'payment_date' => $date,
            'method' => 'fund',
            'note' => $note,
            'fund_transaction_id' => $fund->id,
            'created_by' => $userId ?? 1,
        ]);

        $fund->source_id = $payment->id;
        $fund->save();

        return $payment;
    }

    public function reconcilePurchase(Purchase $purchase): Purchase
    {
        $paid = round((float) $purchase->payments()->sum('amount'), 2);
        $purchase->paid_amount = $paid;
        $purchase->due_amount = max(0, round((float) $purchase->grand_total - $paid, 2));
        $purchase->save();

        return $purchase;
    }

    public function reconcileSupplier(Supplier $supplier): Supplier
    {
        $purchased = (float) $supplier->purchases()->sum('grand_total');
        $paid = (float) $supplier->payments()->sum('amount');
        $supplier->current_due = max(0, round($purchased - $paid, 2));
        $supplier->total_paid = round($paid, 2);
        $supplier->save();

        return $supplier;
    }

    public function reconcilePayment(SupplierPayment $payment): SupplierPayment
    {
        $this->reconcilePurchase($payment->purchase()->firstOrFail());
        $this->reconcileSupplier($payment->supplier()->firstOrFail());

        return $payment->fresh(['fundTransaction']);
    }
}
