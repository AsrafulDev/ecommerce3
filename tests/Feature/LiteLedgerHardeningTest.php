<?php

namespace Tests\Feature;

use App\Enums\TransactionCategory;
use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiteLedgerHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Owner', 'email' => uniqid().'@test.local', 'password' => bcrypt('secret'), 'status' => 1]);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $this->actingAs($this->admin, 'admin');
    }

    public function test_customer_ledger_reconciles_full_partial_multiple_and_decimal_payments(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'slug' => uniqid(), 'phone' => uniqid(), 'password' => bcrypt('x'), 'status' => '1']);
        $this->order($customer, '10000.00', '8000.00');
        $order = $this->order($customer, '1234.55', '234.10');
        OrderPayment::create(['order_id' => $order->id, 'customer_id' => $customer->id, 'amount' => 1000.45, 'payment_method' => 'cash', 'created_by' => $this->admin->id]);

        $order->recalculatePaymentTotals();
        $response = $this->get(route('admin.accounts.customer', $customer->id));
        $response->assertOk()->assertSee('Customer Payment')->assertSee('1,000.45');
        $this->assertEquals(2000.00, (float) Order::where('customer_id', $customer->id)->sum('due_amount'));
    }

    public function test_supplier_ledger_reconciles_multiple_purchases_and_payment_trace(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier', 'phone' => '01700000000']);
        $purchase = Purchase::create(['supplier_id' => $supplier->id, 'invoice_no' => 'P-1', 'grand_total' => 10000, 'paid_amount' => 4000, 'due_amount' => 6000, 'purchase_date' => now(), 'created_by' => $this->admin->id]);
        $fund = FundTransaction::create(['direction' => 'out', 'source' => 'supplier_payment', 'source_id' => null, 'amount' => 4000, 'created_by' => $this->admin->id]);
        $payment = SupplierPayment::create(['supplier_id' => $supplier->id, 'purchase_id' => $purchase->id, 'amount' => 4000, 'payment_date' => now(), 'fund_transaction_id' => $fund->id, 'created_by' => $this->admin->id]);
        $fund->update(['source_id' => $payment->id]);
        $supplier->update(['current_due' => 6000]);

        $this->get(route('admin.accounts.supplier', $supplier->id))->assertOk()->assertSee('6,000.00');
        $this->assertSame(TransactionCategory::SUPPLIER_PAYMENT, $fund->fresh()->transaction_category);
        $this->assertSame($payment->id, $fund->fresh()->source_id);
        $this->assertSame($fund->id, $payment->fund_transaction_id);
    }

    public function test_customer_and_supplier_payment_categories_are_not_income_or_expense(): void
    {
        $in = FundTransaction::create(['direction' => 'in', 'source' => 'sale', 'amount' => 100, 'created_by' => $this->admin->id]);
        $out = FundTransaction::create(['direction' => 'out', 'source' => 'supplier_payment', 'amount' => 100, 'created_by' => $this->admin->id]);
        $this->assertSame(TransactionCategory::SALE, $in->transaction_category);
        $this->assertSame(TransactionCategory::SUPPLIER_PAYMENT, $out->transaction_category);
        $this->assertNotSame(TransactionCategory::OTHER_INCOME, $in->transaction_category);
        $this->assertNotSame(TransactionCategory::EXPENSE, $out->transaction_category);
    }

    private function order(Customer $customer, string $amount, string $paid): Order
    {
        $order = Order::create(['invoice_id' => uniqid('INV-'), 'amount' => $amount, 'paid_amount' => $paid, 'due_amount' => bcsub($amount, $paid, 2), 'discount' => 0, 'shipping_charge' => 0, 'customer_id' => $customer->id, 'order_status' => 'delivered', 'payment_status' => ((float) $paid >= (float) $amount ? 'paid' : 'partial'), 'created_at' => now(), 'updated_at' => now()]);
        if ((float) $paid > 0) {
            OrderPayment::create(['order_id' => $order->id, 'customer_id' => $customer->id, 'amount' => $paid, 'payment_method' => 'cash', 'created_by' => $this->admin->id]);
        }
        return $order;
    }
}
