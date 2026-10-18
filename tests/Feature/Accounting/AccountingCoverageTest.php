<?php

namespace Tests\Feature\Accounting;

use App\Helpers\FundHelper;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Refund;
use App\Services\Registers\FixedAssetService;
use App\Services\Registers\LoanService;
use Softmit\DoubleEntry\Support\AccountRole;

/**
 * Every money or stock event must leave the books in agreement with the business
 * records. Each test goes live, performs ONE event, then checks the controls.
 * A failure names the event whose posting is missing or wrong.
 */
class AccountingCoverageTest extends LiveBooksTestCase
{
    public function test_customer_refund_reaches_the_books(): void
    {
        $this->openBooks();
        $order = Order::latest('id')->firstOrFail();
        $refund = Refund::create([
            'order_id' => $order->id, 'customer_id' => $order->customer_id, 'refund_id' => 'RF-COV-' . uniqid(),
            'amount' => 460, 'shipping_charge' => 0, 'include_shipping' => false, 'reason' => 'Coverage', 'status' => 'pending',
        ]);

        $this->post(route('admin.refunds.approve', $refund->id))->assertRedirect();

        $this->checkControls('after customer refund');
    }

    public function test_loan_taken_and_repaid_reach_the_books(): void
    {
        $this->openBooks();
        $loans = app(LoanService::class);
        $loan = $loans->open([
            'kind' => 'bank', 'direction' => \App\Models\Loan::TAKEN, 'party_name' => 'Bank',
            'amount' => 1000, 'loan_date' => now()->toDateString(),
        ], 1);
        $this->checkControls('after loan taken');

        $loans->repay($loan, 300, now()->toDateString(), 'First instalment', 1);
        $this->checkControls('after loan repayment');
    }

    public function test_fixed_asset_purchase_and_depreciation_reach_the_books(): void
    {
        $this->openBooks();
        $assets = app(FixedAssetService::class);
        $asset = $assets->purchase([
            'name' => 'Shop counter', 'purchase_date' => now()->toDateString(), 'cost' => 800,
        ], 1);
        $this->checkControls('after asset purchase');

        $assets->depreciate($asset, 100, now()->toDateString(), 'Year 1', 1);
        $this->checkControls('after depreciation');
    }

    public function test_stock_adjustment_reaches_the_books(): void
    {
        $this->openBooks();
        $this->post(route('admin.stock.adjustments.store'), [
            'items' => [[
                'product_id' => $this->product->id,
                'type' => 'addition',
                'quantity' => 2,
                'reason' => 'Stock count surplus',
            ]],
        ]);

        $this->assertSame(10, (int) $this->product->fresh()->stock, 'an addition of 2 on 8 on hand gives 10, not 18');
        $this->checkControls('after stock adjustment');
    }

    public function test_purchase_return_reaches_the_books(): void
    {
        $this->openBooks();
        $purchase = Purchase::where('supplier_id', $this->supplier->id)->latest('id')->firstOrFail();
        $item = $purchase->items()->firstOrFail();

        $this->post(route('purchases.item_return', $item->id), ['return_qty' => 2]);

        $this->checkControls('after purchase return');
    }

    public function test_order_return_reaches_the_books(): void
    {
        $this->openBooks();
        $order = Order::latest('id')->firstOrFail();

        $this->post(route('admin.order.requestReturn'), ['order_id' => $order->id]);
        $this->post(route('admin.order.approveReturn'), ['order_id' => $order->id]);
        $this->post(route('admin.order.markReturned'), ['order_id' => $order->id]);

        $this->checkControls('after order return');
    }

    public function test_salary_payment_reaches_the_books(): void
    {
        $this->openBooks();
        $employee = \App\Models\Employee::create([
            'name' => 'Rahim', 'email' => uniqid().'@staff.test', 'employee_id' => 'EMP-' . uniqid(), 'joining_date' => now()->subYear()->toDateString(),
            'basic_salary' => 20000, 'status' => 'active',
        ]);

        $this->post(route('admin.salary_payments.store'), [
            'employee_id' => $employee->id,
            'payment_month' => now()->format('Y-m'),
            'amount' => 15000,
            'payment_method' => 'bank_transfer',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->checkControls('after salary payment');
    }

    public function test_bonus_payment_reaches_the_books(): void
    {
        $this->openBooks();
        $employee = \App\Models\Employee::create([
            'name' => 'Karim', 'email' => uniqid().'@staff.test', 'employee_id' => 'EMP-' . uniqid(), 'joining_date' => now()->subYear()->toDateString(),
            'basic_salary' => 20000, 'status' => 'active',
        ]);
        $bonus = \App\Models\EmployeeBonus::create([
            'employee_id' => $employee->id, 'bonus_type' => 'festival', 'amount' => 2000,
            'status' => 'approved', 'created_by' => 1,
        ]);

        $this->post(route('admin.bonuses.pay', $bonus->id))->assertRedirect();

        $this->checkControls('after bonus payment');
    }

    /**
     * Trading after the opening's as-at date would be counted twice: receivables,
     * payables and stock are read as they stand. The opening must refuse, and say why.
     */
    public function test_opening_is_refused_once_trading_has_moved_on(): void
    {
        config(['double-entry.cutover_date' => now()->toDateString()]);
        $yesterday = now()->subDay()->toDateString();

        $this->post(route('admin.transactions.store'), [
            'date' => $yesterday, 'amount' => 10000, 'category' => 'owner_capital', 'note' => 'Capital',
        ])->assertSessionHasNoErrors();
        $category = \App\Models\Category::create(['name' => 'Bf', 'slug' => 'bf-' . uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = \App\Models\Product::create([
            'name' => 'Backfill Item', 'slug' => 'backfill-' . uniqid(), 'category_id' => $category->id,
            'product_code' => 'BF-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);
        $this->supplier = \App\Models\Supplier::create(['name' => 'Backfill Supplier']);
        $this->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id, 'purchase_date' => $yesterday, 'invoice_no' => 'INV-BF-' . uniqid(),
            'items' => [['product_id' => $this->product->id, 'qty' => 10, 'unit_cost' => 100]], 'paid_amount' => 1000,
        ])->assertSessionHasNoErrors();
        $this->backdate($yesterday);

        // Today, before the opening: a sale of 2 units paid in full.
        $shipping = \App\Models\ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
        \Gloudemans\Shoppingcart\Facades\Cart::instance('pos_shopping')->add([
            'id' => $this->product->id, 'name' => $this->product->name, 'qty' => 2, 'price' => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100],
        ]);
        $this->post('/admin/order/store', [
            'name' => 'Backfill Customer', 'phone' => '01766666666', 'address' => 'Mirpur, Dhaka', 'area' => $shipping->id,
            'payment_type' => 'paid', 'payment_method' => 'Cash', 'paid_amount' => 460, 'save_as_pending' => 0,
        ]);
        $this->assertSame(0, \Softmit\DoubleEntry\Models\JournalEntry::count(), 'held back before the opening');

        $service = app(\App\Services\Accounting\OpeningBalanceService::class);
        $lines = $service->derive()['lines'];
        if ($plug = $service->balancingLine($lines)) {
            $lines[] = $plug;
        }

        try {
            $service->saveDraft($lines, $yesterday, 1);
            $this->fail('an opening dated before a sale must be refused');
        } catch (\Softmit\DoubleEntry\Exceptions\AccountingException $e) {
            $this->assertStringContainsString('Trading has moved on', $e->getMessage());
            $this->assertStringContainsString('1 sales', $e->getMessage());
        }

        $this->assertFalse(app(\App\Services\Accounting\AdvancedAccountingGateway::class)->readyForLivePosting());
    }
}
