<?php

namespace Tests\Feature\Accounting;

use App\Helpers\FundHelper;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\OpeningBalanceService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Support\AccountRole;

/**
 * Shared harness for "the books follow the business" tests.
 *
 * openBooks() runs the real go-live path: operational records are created while
 * Advanced is not ready, the opening is derived from them as at yesterday, the
 * balancing line is added, and the opening is posted. Everything after that is
 * live and must be journaled.
 *
 * checkControls() is the invariant every event must leave true:
 *
 *   A = L + E + net income              (the accounting equation)
 *   cash        == fund till            (Lite and Advanced agree on money)
 *   payables    == Σ supplier dues
 *   receivables == Σ open customer dues
 *   inventory   == Σ batch qty × cost
 */
abstract class LiveBooksTestCase extends AccountingTestCase
{
    protected Product $product;
    protected Supplier $supplier;
    protected ShippingCharge $shipping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    /** Pre-go-live records, opening, and the product/supplier used by the events. */
    protected function openBooks(): void
    {
        config(['double-entry.cutover_date' => now()->toDateString()]);
        $yesterday = now()->subDay()->toDateString();

        $this->post(route('admin.transactions.store'), [
            'date' => $yesterday, 'amount' => 10000, 'category' => 'owner_capital', 'note' => 'Capital',
        ])->assertSessionHasNoErrors();

        $category = Category::create(['name' => 'Acc', 'slug' => 'acc-' . uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Ledger Item', 'slug' => 'ledger-item-' . uniqid(), 'category_id' => $category->id,
            'product_code' => 'LI-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);
        $this->supplier = Supplier::create(['name' => 'Ledger Supplier']);
        $this->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id, 'purchase_date' => $yesterday, 'invoice_no' => 'INV-ACC-' . uniqid(),
            'items' => [['product_id' => $this->product->id, 'qty' => 10, 'unit_cost' => 100]], 'paid_amount' => 600,
        ])->assertSessionHasNoErrors();

        $this->shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
        Cart::instance('pos_shopping')->add([
            'id' => $this->product->id, 'name' => $this->product->name, 'qty' => 2, 'price' => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100],
        ]);
        $this->post('/admin/order/store', [
            'name' => 'Ledger Customer', 'phone' => '01744444444', 'address' => 'Mirpur, Dhaka', 'area' => $this->shipping->id,
            'payment_type' => 'paid', 'payment_method' => 'Cash', 'paid_amount' => 460, 'save_as_pending' => 0,
        ]);

        $this->backdate($yesterday);

        $this->assertFalse(app(AdvancedAccountingGateway::class)->readyForLivePosting(), 'not live before the opening');

        $service = app(OpeningBalanceService::class);
        $lines = $service->derive()['lines'];
        if ($plug = $service->balancingLine($lines)) {
            $lines[] = $plug;
        }
        $service->saveDraft($lines, $yesterday, 1);
        $service->post($service->draft());

        $this->assertTrue(app(AdvancedAccountingGateway::class)->readyForLivePosting());
        $this->checkControls('opening');
    }

    /** Operational records written before go-live are carried by the opening; this moves them to yesterday. */
    protected function backdate(string $day): void
    {
        $at = $day . ' 12:00:00';
        foreach (['fund_transactions', 'orders', 'order_payments', 'order_details', 'purchases',
                  'purchase_items', 'supplier_payments', 'stock_batches'] as $table) {
            DB::table($table)->update(['created_at' => $at, 'updated_at' => $at]);
        }
        DB::table('purchases')->update(['purchase_date' => $day]);
    }

    /** Sums posted and reversed journal lines by account type. */
    protected function equation(): array
    {
        $rows = DB::table('accounting_journal_lines as l')
            ->join('accounting_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('accounting_accounts as a', 'a.id', '=', 'l.account_id')
            ->whereIn('j.status', ['posted', 'reversed'])
            ->groupBy('a.account_type')
            ->selectRaw('a.account_type, SUM(l.debit) AS d, SUM(l.credit) AS c')
            ->get()
            ->keyBy('account_type');

        $dr = fn (string $type) => (float) ($rows[$type]->d ?? 0);
        $cr = fn (string $type) => (float) ($rows[$type]->c ?? 0);

        $assets = $dr('asset') - $cr('asset');
        $liabilities = $cr('liability') - $dr('liability');
        $equity = $cr('equity') - $dr('equity');
        $netIncome = ($cr('revenue') - $dr('revenue'))
            - (($dr('cost_of_sales') - $cr('cost_of_sales')) + ($dr('expense') - $cr('expense')));

        $debits = $dr('asset') + $dr('liability') + $dr('equity') + $dr('revenue') + $dr('cost_of_sales') + $dr('expense');
        $credits = $cr('asset') + $cr('liability') + $cr('equity') + $cr('revenue') + $cr('cost_of_sales') + $cr('expense');

        return compact('assets', 'liabilities', 'equity', 'netIncome', 'debits', 'credits');
    }

    /** Natural-sign balance of one account role across posted and reversed journals. */
    protected function balanceOf(string $role): float
    {
        $account = Account::where('role', $role)->firstOrFail();
        $row = DB::table('accounting_journal_lines as l')
            ->join('accounting_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->whereIn('j.status', ['posted', 'reversed'])
            ->where('l.account_id', $account->id)
            ->selectRaw('SUM(l.debit) AS d, SUM(l.credit) AS c')
            ->first();

        $normal = $account->normal_balance instanceof \BackedEnum ? $account->normal_balance->value : (string) $account->normal_balance;

        return $normal === 'debit'
            ? (float) $row->d - (float) $row->c
            : (float) $row->c - (float) $row->d;
    }

    /**
     * Receivables should equal what customers still owe, less what we owe back to them:
     * the dues on recognised orders, minus the paid value of returned orders that has not
     * been refunded yet.
     */
    protected function openCustomerDues(): float
    {
        $due = (float) Order::whereIn('order_status', \App\Services\CogsCalculator::RECOGNIZED_STATUSES)->sum('due_amount');

        $returned = Order::where('order_status', 'returned');
        $paid = (float) $returned->sum('paid_amount');
        $refunded = (float) \App\Models\Refund::whereIn('order_id', $returned->pluck('id'))
            ->whereIn('status', ['approved', 'processed'])
            ->sum('amount');

        return round($due - ($paid - $refunded), 2);
    }

    protected function checkControls(string $when): void
    {
        $e = $this->equation();

        $this->assertEqualsWithDelta($e['debits'], $e['credits'], 0.01, "{$when}: journals must balance");
        $this->assertEqualsWithDelta(
            $e['assets'],
            $e['liabilities'] + $e['equity'] + $e['netIncome'],
            0.01,
            "{$when}: A = L + E + net income"
        );
        $this->assertEqualsWithDelta(FundHelper::balance(), $this->balanceOf(AccountRole::CASH), 0.01, "{$when}: cash == till");
        $this->assertEqualsWithDelta(
            (float) Supplier::find($this->supplier->id)->current_due,
            $this->balanceOf(AccountRole::ACCOUNTS_PAYABLE),
            0.01,
            "{$when}: payables == supplier dues"
        );
        $this->assertEqualsWithDelta($this->openCustomerDues(), $this->balanceOf(AccountRole::ACCOUNTS_RECEIVABLE), 0.01, "{$when}: receivables == open dues");
        $this->assertEqualsWithDelta(
            (float) StockBatch::where('product_id', $this->product->id)->selectRaw('SUM(remaining_qty * unit_cost) AS v')->value('v'),
            $this->balanceOf(AccountRole::INVENTORY),
            0.01,
            "{$when}: inventory == batch valuation"
        );
    }
}
