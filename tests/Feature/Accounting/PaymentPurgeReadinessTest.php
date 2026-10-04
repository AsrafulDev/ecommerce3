<?php

namespace Tests\Feature\Accounting;

use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use App\Services\Accounting\PaymentPurgeReadinessService;

class PaymentPurgeReadinessTest extends AccountingTestCase
{
    public function test_customer_legacy_aggregate_fund_remains_blocked_and_preserves_due_evidence(): void
    {
        $order = $this->order(['amount' => 10000, 'paid_amount' => 5000, 'due_amount' => 5000, 'payment_status' => 'partial']);
        $first = OrderPayment::create(['order_id' => $order->id, 'amount' => 3000, 'payment_method' => 'Cash', 'created_by' => 1]);
        OrderPayment::create(['order_id' => $order->id, 'amount' => 2000, 'payment_method' => 'Cash', 'created_by' => 1]);
        FundTransaction::create(['direction' => 'in', 'source' => 'sale', 'source_id' => $order->id, 'amount' => 5000, 'created_by' => 1]);

        $result = app(PaymentPurgeReadinessService::class)->customer($first);

        $this->assertSame('BLOCKED', $result['status']);
        $this->assertContains('MISSING_FUND_TRANSACTION', $result['blockers']);
        $this->assertContains('LEGACY_ORDER_AGGREGATE_FUND_EXISTS', $result['blockers']);
        $this->assertSame(2, $order->paymentHistory()->count());
        $this->assertSame('5000.00', (string) $order->fresh()->due_amount);
    }

    public function test_customer_refund_and_cancelled_order_are_explicit_blockers(): void
    {
        $order = $this->order(['order_status' => 'cancelled', 'amount' => 1000, 'paid_amount' => 1000, 'due_amount' => 0, 'payment_status' => 'paid']);
        $payment = OrderPayment::create(['order_id' => $order->id, 'amount' => 1000, 'payment_method' => 'Cash', 'created_by' => 1]);
        \App\Models\Refund::create(['order_id' => $order->id, 'customer_id' => 1, 'amount' => 100, 'refund_id' => 'REF-READINESS', 'status' => 'approved']);

        $result = app(PaymentPurgeReadinessService::class)->customer($payment);

        $this->assertContains('DEPENDENT_REFUND_EXISTS', $result['blockers']);
        $this->assertContains('ORDER_CANCELLED_OR_RETURNED', $result['blockers']);
    }

    public function test_supplier_payment_requires_exact_fund_link_and_blocks_supplier_returns(): void
    {
        $supplier = Supplier::create(['name' => 'Readiness Supplier', 'current_due' => 6000]);
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'invoice_no' => 'READINESS-1', 'grand_total' => 10000, 'paid_amount' => 4000, 'due_amount' => 6000, 'purchase_date' => now()->toDateString(), 'created_by' => 1]);
        $fund = FundTransaction::create(['direction' => 'out', 'source' => 'supplier_payment', 'source_id' => 999999, 'amount' => 4000, 'created_by' => 1]);
        $payment = SupplierPayment::create(['supplier_id' => $supplier->id, 'purchase_id' => $purchase->id, 'amount' => 4000, 'payment_date' => now()->toDateString(), 'fund_transaction_id' => $fund->id, 'created_by' => 1]);
        SupplierReturn::create(['supplier_id' => $supplier->id, 'purchase_id' => $purchase->id, 'return_no' => 'RET-READINESS', 'return_date' => now()->toDateString(), 'total_amount' => 100, 'created_by' => 1]);

        $result = app(PaymentPurgeReadinessService::class)->supplier($payment);

        $this->assertContains('FUND_TRANSACTION_LINK_MISMATCH', $result['blockers']);
        $this->assertContains('DEPENDENT_SUPPLIER_RETURN_EXISTS', $result['blockers']);
        $this->assertContains('SUPPLIER_DUE_RECOMPUTATION_REQUIRED', $result['blockers']);
    }

    public function test_supplier_missing_fund_link_is_blocked_even_when_advanced_is_off(): void
    {
        config(['double-entry.enabled' => false]);
        $supplier = Supplier::create(['name' => 'Lite Supplier']);
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'invoice_no' => 'READINESS-2', 'grand_total' => 100, 'purchase_date' => now()->toDateString(), 'created_by' => 1]);
        $payment = SupplierPayment::create(['supplier_id' => $supplier->id, 'purchase_id' => $purchase->id, 'amount' => 50, 'payment_date' => now()->toDateString(), 'created_by' => 1]);

        $result = app(PaymentPurgeReadinessService::class)->supplier($payment);

        $this->assertContains('MISSING_FUND_TRANSACTION', $result['blockers']);
        $this->assertContains('PAYMENT_PURGE_NOT_ENABLED_PHASE_2B', $result['blockers']);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'invoice_id' => 'READINESS-'.uniqid(), 'amount' => 10000, 'paid_amount' => 0,
            'due_amount' => 10000, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'payment_status' => 'pending',
            'order_type' => 'online',
        ], $overrides));
    }
}
