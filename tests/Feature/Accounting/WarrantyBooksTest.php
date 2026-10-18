<?php

namespace Tests\Feature\Accounting;

use App\Helpers\FundHelper;
use App\Models\Expense;
use App\Models\Order;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use Softmit\DoubleEntry\Support\AccountRole;

/**
 * D6: the warranty money (a supplier charge, paid as an expense, and a customer charge,
 * received as income) must reach the books, with the till, cash and control accounts
 * still agreeing afterwards.
 */
class WarrantyBooksTest extends LiveBooksTestCase
{
    private function claim(): WarrantyClaim
    {
        $order = Order::latest('id')->firstOrFail();
        // The POS sale already created a warranty sale for this line; reuse it.
        $sale = WarrantySale::where('order_id', $order->id)->first() ?? WarrantySale::create([
            'order_id' => $order->id, 'order_detail_id' => $order->orderdetails()->value('id'),
            'product_id' => $this->product->id, 'customer_id' => $order->customer_id,
            'warranty_type' => 'store_warranty', 'warranty_days' => 365, 'warranty_price' => 0,
            'is_transferable' => false, 'status' => 'active',
        ]);

        return WarrantyClaim::create([
            'warranty_sale_id' => $sale->id, 'customer_id' => $order->customer_id, 'order_id' => $order->id,
            'product_id' => $this->product->id, 'claim_number' => 'WC-BOOKS-' . uniqid(),
            'issue_description' => 'Cone cracked', 'issue_type' => 'defect', 'status' => 'approved',
            'claimed_at' => now(), 'servicing_cost' => 0, 'store_bears_cost' => 0,
        ]);
    }

    public function test_customer_charge_on_delivery_reaches_the_books_as_warranty_income(): void
    {
        $this->openBooks();
        $claim = $this->claim();

        $this->post(route('admin.warranty.claims.deliver', $claim->id), ['customer_charge' => 40])
            ->assertRedirect();

        $this->checkControls('after warranty customer charge');
        $this->assertEqualsWithDelta(40.0, $this->balanceOf(AccountRole::WARRANTY_INCOME), 0.01, 'the charge is warranty income');
    }

    public function test_supplier_charge_on_supplier_return_reaches_the_books_as_an_expense(): void
    {
        $this->openBooks();
        $claim = $this->claim();
        $tillBefore = FundHelper::balance();

        $this->post(route('admin.warranty.claims.supplier-return', $claim->id), [
            'return_type' => 'repaired', 'supplier_charge' => 80, 'add_to_expenses' => 1,
        ])->assertRedirect();

        $this->assertSame(1, Expense::where('category', 'warranty')->count());
        $this->assertEqualsWithDelta($tillBefore - 80, FundHelper::balance(), 0.01, 'the charge leaves the till');
        $this->checkControls('after warranty supplier charge');
    }
}
