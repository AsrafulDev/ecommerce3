<?php

namespace Tests\Feature\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\User;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\DoubleEntryAdvancedAccountingGateway;
use App\Services\Accounting\ManualEntryService;
use App\Services\Accounting\NullAdvancedAccountingGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Models\JournalEntry;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * MODE A of the two-mode testing rule: Advanced Accounting OFF.
 *
 * The architectural guarantee under test: with the optional module switched
 * off, commerce and Lite Accounting keep working, no journal is written, and
 * no package-backed service is even constructed. Deliberately does NOT extend
 * AccountingTestCase and deliberately does NOT seed the chart of accounts —
 * the Lite flows must not need any of that.
 */
class AdvancedAccountingDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // The exact state of a Lite-only install: package present but the
        // operator never switched Advanced Accounting on.
        config(['double-entry.enabled' => false]);

        $this->admin = User::create([
            'name'     => 'Owner',
            'email'    => 'owner@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $this->admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin->unsetRelation('roles');

        $this->actingAs($this->admin, 'admin');
    }

    public function test_the_container_binds_the_null_gateway_when_disabled(): void
    {
        $gateway = app(AdvancedAccountingGateway::class);

        $this->assertInstanceOf(NullAdvancedAccountingGateway::class, $gateway);
        $this->assertFalse($gateway->enabled());
    }

    public function test_fund_cash_in_works_and_writes_no_journal(): void
    {
        $this->post(route('admin.fund.add'), [
            'amount' => 5000,
            'note'   => 'Owner put money in',
            'nature' => 'owner_capital',
        ])->assertRedirect();

        $tx = FundTransaction::latest('id')->firstOrFail();
        $this->assertSame('in', $tx->direction);
        $this->assertSame('manual_add', $tx->source);
        $this->assertEquals(5000, (float) $tx->amount);

        $this->assertSame(0, JournalEntry::count(), 'disabled module must never journal');
        $this->assertSame(0, DB::table('accounting_posting_failures')->count());
    }

    public function test_fund_cash_out_works_and_writes_no_journal(): void
    {
        FundTransaction::create([
            'direction' => 'in', 'source' => 'manual_add', 'amount' => 1000,
            'note' => 'float', 'created_by' => $this->admin->id,
        ]);

        $this->post(route('admin.fund.withdraw'), [
            'amount' => 400,
            'note'   => 'Owner took money out',
        ])->assertRedirect();

        $out = FundTransaction::where('direction', 'out')->latest('id')->firstOrFail();
        $this->assertSame('withdraw', $out->source);
        $this->assertEquals(400, (float) $out->amount);

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_expense_creation_works_and_writes_no_journal(): void
    {
        FundTransaction::create([
            'direction' => 'in', 'source' => 'manual_add', 'amount' => 1000,
            'note' => 'float', 'created_by' => $this->admin->id,
        ]);

        $this->post(route('admin.expenses.store'), [
            'title'        => 'Office rent',
            'amount'       => 800,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => 'rent',
        ])->assertRedirect();

        $expense = Expense::latest('id')->firstOrFail();
        $this->assertEquals(800, (float) $expense->amount);
        $this->assertNotNull($expense->fund_transaction_id, 'Lite expense still links its fund OUT row');
        $this->assertSame('expense', FundTransaction::find($expense->fund_transaction_id)->source);

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_posted_journal_locks_do_not_block_editing_when_disabled(): void
    {
        $expense = Expense::create([
            'title' => 'Old rent', 'amount' => 100,
            'expense_date' => now()->format('Y-m-d'), 'created_by' => $this->admin->id,
        ]);

        // With the books off, no row is ever locked: edit and delete stay open.
        $this->get(route('admin.expenses.edit', $expense->id))->assertOk();

        $this->delete(route('admin.expenses.destroy', $expense->id))->assertRedirect();
        $this->assertSame(0, Expense::count());
    }

    public function test_lite_money_request_never_constructs_package_backed_services(): void
    {
        FundTransaction::create([
            'direction' => 'in', 'source' => 'manual_add', 'amount' => 500,
            'note' => 'float', 'created_by' => $this->admin->id,
        ]);

        $this->post(route('admin.expenses.store'), [
            'title'        => 'Electricity',
            'amount'       => 100,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => 'utility',
        ])->assertRedirect();

        $this->assertFalse(
            $this->app->resolved(ManualEntryService::class),
            'a Lite-only request must not resolve the double-entry integration layer'
        );
        $this->assertFalse($this->app->resolved(DoubleEntryAdvancedAccountingGateway::class));
    }

    public function test_advanced_accounting_screens_are_dead_when_disabled(): void
    {
        // 404, not 403: to a disabled install the module simply does not exist.
        $this->get(route('admin.accounting.journals.index'))->assertNotFound();
        $this->get(route('admin.accounting.reports.trial-balance'))->assertNotFound();
    }

    public function test_accounts_dashboard_and_basic_profit_render_without_advanced_accounting(): void
    {
        $this->get(route('admin.accounts.dashboard'))->assertOk();
        $this->get(route('admin.reports.profit_loss'))->assertOk();
        $this->get(route('admin.fund.index'))->assertOk();
        $this->get(route('admin.expenses.index'))->assertOk();
    }
}
