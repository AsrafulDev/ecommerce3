<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Receipt and payment entry: many invoices, one save, every line checked before
 * anything is booked.
 */
class AccountsEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['double-entry.enabled' => false]);

        $this->admin = User::forceCreate([
            'id' => 2, 'name' => 'Owner', 'email' => 'owner@test.local', 'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $this->actingAs($this->admin, 'admin');
    }

    private function customer(string $name = 'Rahim'): Customer
    {
        return Customer::create(['name' => $name, 'slug' => uniqid(), 'phone' => uniqid(), 'password' => bcrypt('x'), 'status' => '1']);
    }

    private function order(Customer $customer, string $amount, string $paid = '0.00'): Order
    {
        return Order::create([
            'invoice_id' => uniqid('INV-'), 'amount' => $amount, 'paid_amount' => $paid,
            'due_amount' => bcsub($amount, $paid, 2), 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $customer->id, 'order_status' => 'delivered',
            'payment_status' => 'partial', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function fundCash(float $amount): void
    {
        FundTransaction::create(['direction' => 'in', 'source' => 'manual_add', 'amount' => $amount, 'note' => 'opening cash', 'created_by' => $this->admin->id]);
    }

    private function supplierWithPurchase(float $grandTotal): array
    {
        $supplier = Supplier::create(['name' => 'Velnex', 'phone' => '01700000000']);
        $purchase = Purchase::create([
            'supplier_id' => $supplier->id, 'invoice_no' => 'PIG-1', 'grand_total' => $grandTotal,
            'paid_amount' => 0, 'due_amount' => $grandTotal, 'purchase_date' => now(), 'created_by' => $this->admin->id,
        ]);

        return [$supplier, $purchase];
    }

    public function test_a_receipt_across_two_invoices_is_booked_and_reduces_both_dues(): void
    {
        $customer = $this->customer();
        $first = $this->order($customer, '1000.00');
        $second = $this->order($customer, '500.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $customer->id,
            'method' => 'Cash',
            'amounts' => [$first->id => '400', $second->id => '500'],
        ])->assertSessionHas('success');

        $this->assertEquals(600.00, (float) $first->fresh()->due_amount);
        $this->assertEquals(0.00, (float) $second->fresh()->due_amount);
        $this->assertSame(2, OrderPayment::where('customer_id', $customer->id)->count());
    }

    public function test_a_receipt_above_an_invoice_due_books_nothing(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, '1000.00');
        $other = $this->order($customer, '300.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $customer->id,
            'method' => 'Cash',
            'amounts' => [$other->id => '100', $order->id => '1500'],
        ])->assertSessionHas('error');

        // The valid line on the same form is not booked either.
        $this->assertEquals(1000.00, (float) $order->fresh()->due_amount);
        $this->assertEquals(300.00, (float) $other->fresh()->due_amount);
        $this->assertSame(0, OrderPayment::count());
    }

    public function test_a_receipt_cannot_be_put_against_another_customers_invoice(): void
    {
        $owner = $this->customer('Owner Co');
        $stranger = $this->customer('Stranger');
        $order = $this->order($owner, '800.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $stranger->id,
            'method' => 'Cash',
            'amounts' => [$order->id => '200'],
        ])->assertSessionHas('error');

        $this->assertEquals(800.00, (float) $order->fresh()->due_amount);
    }

    public function test_a_supplier_payment_reduces_the_purchase_due_and_the_fund(): void
    {
        $this->fundCash(10000);
        [$supplier, $purchase] = $this->supplierWithPurchase(6000);

        $this->post(route('admin.accounts.entry.payment.store'), [
            'supplier_id' => $supplier->id,
            'payment_date' => now()->toDateString(),
            'amounts' => [$purchase->id => '2500'],
        ])->assertSessionHas('success');

        $this->assertEquals(3500.00, (float) $purchase->fresh()->due_amount);
        $this->assertSame(1, SupplierPayment::where('purchase_id', $purchase->id)->count());
        $this->assertEquals(7500.00, \App\Helpers\FundHelper::balance());
    }

    public function test_a_supplier_payment_above_the_fund_balance_books_nothing(): void
    {
        $this->fundCash(1000);
        [$supplier, $purchase] = $this->supplierWithPurchase(6000);

        $this->post(route('admin.accounts.entry.payment.store'), [
            'supplier_id' => $supplier->id,
            'payment_date' => now()->toDateString(),
            'amounts' => [$purchase->id => '2500'],
        ])->assertSessionHas('error');

        $this->assertEquals(6000.00, (float) $purchase->fresh()->due_amount);
        $this->assertSame(0, SupplierPayment::count());
    }

    public function test_a_supplier_payment_above_a_purchase_due_books_nothing(): void
    {
        $this->fundCash(10000);
        [$supplier, $purchase] = $this->supplierWithPurchase(6000);

        $this->post(route('admin.accounts.entry.payment.store'), [
            'supplier_id' => $supplier->id,
            'payment_date' => now()->toDateString(),
            'amounts' => [$purchase->id => '7000'],
        ])->assertSessionHas('error');

        $this->assertSame(0, SupplierPayment::count());
    }

    public function test_staff_without_fund_create_cannot_open_or_save_an_entry(): void
    {
        $staff = User::forceCreate(['id' => 3, 'name' => 'Clerk', 'email' => 'clerk@test.local', 'password' => bcrypt('x'), 'status' => 1]);
        $role = Role::firstOrCreate(['name' => 'clerk', 'guard_name' => 'admin']);
        $role->syncPermissions([Permission::where('name', 'fund-list')->where('guard_name', 'admin')->firstOrFail()]);
        $staff->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($staff, 'admin');

        $this->get(route('admin.accounts.entry.receipt'))->assertForbidden();
        $this->post(route('admin.accounts.entry.payment.store'), [])->assertForbidden();
    }

    public function test_a_saved_supplier_payment_appears_on_the_payment_record(): void
    {
        $this->fundCash(10000);
        [$supplier, $purchase] = $this->supplierWithPurchase(6000);

        $this->post(route('admin.accounts.entry.payment.store'), [
            'supplier_id' => $supplier->id,
            'payment_date' => now()->toDateString(),
            'amounts' => [$purchase->id => '2500'],
        ]);

        $this->get(route('admin.accounts.reports.show', 'payment-record'))
            ->assertOk()
            ->assertSee('Velnex')
            ->assertSee('PIG-1')
            ->assertSee('2,500.00');
    }

    public function test_a_receipt_method_outside_the_order_dialog_list_is_refused(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, '1000.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $customer->id,
            'method' => 'Barter',
            'amounts' => [$order->id => '100'],
        ])->assertSessionHasErrors('method');

        $this->assertEquals(1000.00, (float) $order->fresh()->due_amount);
    }

    public function test_a_receipt_takes_the_chosen_date(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, '1000.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $customer->id,
            'paid_on' => '2026-10-01',
            'method' => 'Cash',
            'amounts' => [$order->id => '400'],
        ])->assertSessionHas('success');

        $payment = OrderPayment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('2026-10-01', $payment->created_at->toDateString());
        $this->assertSame('2026-10-01', FundTransaction::where('source', 'customer_payment')->firstOrFail()->created_at->toDateString());
    }

    public function test_a_discount_lowers_the_due_but_moves_no_cash(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, '1000.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $customer->id,
            'paid_on' => now()->toDateString(),
            'method' => 'Cash',
            'amounts' => [$order->id => '400'],
            'discounts' => [$order->id => '100'],
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(500.00, (float) $order->due_amount);
        $this->assertEquals(400.00, (float) $order->paid_amount);
        $this->assertEquals(400.00, (float) FundTransaction::where('direction', 'in')->sum('amount'));
    }

    public function test_cash_plus_adjustments_above_the_due_books_nothing(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, '1000.00');

        $this->post(route('admin.accounts.entry.receipt.store'), [
            'customer_id' => $customer->id,
            'paid_on' => now()->toDateString(),
            'method' => 'Cash',
            'amounts' => [$order->id => '800'],
            'vats' => [$order->id => '300'],
        ])->assertSessionHas('error');

        $this->assertEquals(1000.00, (float) $order->fresh()->due_amount);
        $this->assertSame(0, OrderPayment::count());
    }
}
