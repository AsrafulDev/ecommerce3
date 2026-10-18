<?php

namespace Tests\Feature\Accounting;

use App\Helpers\FundHelper;
use App\Models\Category;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\OpeningBalanceService;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Support\AccountRole;

/**
 * The books must follow the accounting equation after every business event:
 *
 *     Assets = Liabilities + Equity + Net income
 *
 * and the control accounts must agree with the operational records:
 *
 *   cash         == fund till                      (Lite ↔ Advanced)
 *   receivables  == Σ open customer dues
 *   payables     == Σ supplier current dues
 *   inventory    == Σ batch qty × unit cost
 */
class AccountingEquationTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    /** Sums posted and reversed journal lines by account type. */
    private function equation(): array
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
        $income = ($cr('revenue') - $dr('revenue'));
        $costs = ($dr('cost_of_sales') - $cr('cost_of_sales')) + ($dr('expense') - $cr('expense'));
        $netIncome = $income - $costs;

        return compact('assets', 'liabilities', 'equity', 'netIncome')
            + ['debits' => $dr('asset') + $dr('liability') + $dr('equity') + $dr('revenue') + $dr('cost_of_sales') + $dr('expense'),
               'credits' => $cr('asset') + $cr('liability') + $cr('equity') + $cr('revenue') + $cr('cost_of_sales') + $cr('expense')];
    }

    /** Moves the operational rows written so far to the given day (test setup only). */
    private function backdate(string $day): void
    {
        $at = $day.' 12:00:00';
        foreach (['fund_transactions', 'orders', 'order_payments', 'order_details', 'purchases',
                  'purchase_items', 'supplier_payments', 'stock_batches'] as $table) {
            DB::table($table)->update(['created_at' => $at, 'updated_at' => $at]);
        }
        DB::table('purchases')->update(['purchase_date' => $day]);
    }

    private function balanceOf(string $role): float
    {
        $account = \Softmit\DoubleEntry\Models\Account::where('role', $role)->firstOrFail();
        $row = DB::table('accounting_journal_lines as l')
            ->join('accounting_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->whereIn('j.status', ['posted', 'reversed'])
            ->where('l.account_id', $account->id)
            ->selectRaw('SUM(l.debit) AS d, SUM(l.credit) AS c')
            ->first();

        // Natural sign: debit-normal for assets/expenses, credit-normal otherwise.
        $normal = $account->normal_balance instanceof \BackedEnum ? $account->normal_balance->value : (string) $account->normal_balance;

        return $normal === 'debit'
            ? (float) $row->d - (float) $row->c
            : (float) $row->c - (float) $row->d;
    }

    private function assertBooksBalance(string $when): void
    {
        $e = $this->equation();

        $this->assertEqualsWithDelta($e['debits'], $e['credits'], 0.01, "{$when}: journals must balance");
        $this->assertEqualsWithDelta(
            $e['assets'],
            $e['liabilities'] + $e['equity'] + $e['netIncome'],
            0.01,
            "{$when}: A = L + E + net income"
        );
    }

    public function test_books_follow_the_accounting_equation_through_the_lifecycle(): void
    {
        // Cutover is today: everything dated before it belongs to the opening.
        config(['double-entry.cutover_date' => now()->toDateString()]);
        $yesterday = now()->subDay()->toDateString();

        // ── Before go-live: operational records only. Advanced is not ready, so
        //    nothing is journaled yet; the opening will carry all of it forward.
        $this->assertFalse(app(\App\Services\Accounting\AdvancedAccountingGateway::class)->readyForLivePosting());

        $this->post(route('admin.transactions.store'), [
            'date' => $yesterday, 'amount' => 10000, 'category' => 'owner_capital', 'note' => 'Capital',
        ])->assertSessionHasNoErrors();

        $category = Category::create(['name' => 'Acc', 'slug' => 'acc-' . uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Ledger Item', 'slug' => 'ledger-item-' . uniqid(), 'category_id' => $category->id,
            'product_code' => 'LI-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);
        $supplier = Supplier::create(['name' => 'Ledger Supplier']);
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => $yesterday, 'invoice_no' => 'INV-ACC-' . uniqid(),
            'items' => [['product_id' => $product->id, 'qty' => 10, 'unit_cost' => 100]], 'paid_amount' => 600,
        ])->assertSessionHasNoErrors();

        $shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
        Cart::instance('pos_shopping')->add([
            'id' => $product->id, 'name' => $product->name, 'qty' => 2, 'price' => 200,
            'options' => ['product_id' => $product->id, 'purchase_price' => 100],
        ]);
        $this->post('/admin/order/store', [
            'name' => 'Ledger Customer', 'phone' => '01744444444', 'address' => 'Mirpur, Dhaka', 'area' => $shipping->id,
            'payment_type' => 'paid', 'payment_method' => 'Cash', 'paid_amount' => 460, 'save_as_pending' => 0,
        ]);

        // Those records happened yesterday, before the cutover.
        $this->backdate($yesterday);

        // ── Go-live: opening as at yesterday, from the records, plus the balancing line.
        $service = app(OpeningBalanceService::class);
        $lines = $service->derive()['lines'];
        if ($plug = $service->balancingLine($lines)) {
            $lines[] = $plug;
        }
        $service->saveDraft($lines, $yesterday, 1);
        $service->post($service->draft());
        $this->assertTrue(app(\App\Services\Accounting\AdvancedAccountingGateway::class)->readyForLivePosting());
        $this->assertBooksBalance('opening');
        $this->assertEqualsWithDelta(FundHelper::balance(), $this->balanceOf(AccountRole::CASH), 0.01, 'cash == till at opening');
        $this->assertEqualsWithDelta(800.0, $this->balanceOf(AccountRole::INVENTORY), 0.01, 'inventory == 8 x 100 at opening (2 sold)');

        // ── Live: expense rent 50
        $this->post(route('admin.transactions.store'), [
            'date' => now()->toDateString(), 'amount' => 50, 'category' => 'rent', 'note' => 'Rent',
        ])->assertSessionHasNoErrors();
        $this->assertBooksBalance('after expense');
        $this->assertEqualsWithDelta(FundHelper::balance(), $this->balanceOf(AccountRole::CASH), 0.01, 'cash == till after expense');

        // ── Live: pay the remaining supplier due 400
        $purchase = \App\Models\Purchase::where('supplier_id', $supplier->id)->firstOrFail();
        $this->post(route('purchases.pay_due', $purchase->id), ['amount' => 400, 'payment_date' => now()->toDateString()]);
        $this->assertBooksBalance('after supplier payment');
        $this->assertEqualsWithDelta(
            (float) Supplier::find($supplier->id)->current_due,
            $this->balanceOf(AccountRole::ACCOUNTS_PAYABLE),
            0.01,
            'payables == supplier dues after payment'
        );
        $this->assertEqualsWithDelta(FundHelper::balance(), $this->balanceOf(AccountRole::CASH), 0.01, 'cash == till after supplier payment');

        // ── Live: a second sale, 1 unit, paid in full (260 incl. delivery)
        $this->post('/admin/order/store', [
            'name' => 'Ledger Customer 2', 'phone' => '01755555555', 'address' => 'Mirpur, Dhaka', 'area' => $shipping->id,
            'payment_type' => 'paid', 'payment_method' => 'Cash', 'paid_amount' => 260, 'save_as_pending' => 0,
        ]);
        $this->assertBooksBalance('after live sale');
        $this->assertEqualsWithDelta(FundHelper::balance(), $this->balanceOf(AccountRole::CASH), 0.01, 'cash == till after live sale');
        $this->assertEqualsWithDelta(
            (float) Order::latest('id')->value('due_amount'),
            $this->balanceOf(AccountRole::ACCOUNTS_RECEIVABLE),
            0.01,
            'receivables == open dues after live sale'
        );

        // ── Live: buy 5 more units @ 100 on credit (nothing paid)
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(), 'invoice_no' => 'INV-LIVE-' . uniqid(),
            'items' => [['product_id' => $product->id, 'qty' => 5, 'unit_cost' => 100]], 'paid_amount' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertBooksBalance('after live purchase');
        $this->assertEqualsWithDelta(
            (float) Supplier::find($supplier->id)->current_due,
            $this->balanceOf(AccountRole::ACCOUNTS_PAYABLE),
            0.01,
            'payables == supplier dues after live purchase'
        );
        $this->assertEqualsWithDelta(500.0, $this->balanceOf(AccountRole::ACCOUNTS_PAYABLE), 0.01, 'payable 500 for the credit purchase');

        // ── Inventory control: 7 old units + 5 new units at cost
        $this->assertEqualsWithDelta(
            (float) StockBatch::where('product_id', $product->id)->selectRaw('SUM(remaining_qty * unit_cost) AS v')->value('v'),
            $this->balanceOf(AccountRole::INVENTORY),
            0.01,
            'inventory == batch valuation (7 + 5 units)'
        );
    }
}
