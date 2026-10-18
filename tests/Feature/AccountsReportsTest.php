<?php

namespace Tests\Feature;

use App\Models\FundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The Finance & Reports pages: every built report opens, the links go where they
 * should, and the cash figures come from the funds ledger.
 */
class AccountsReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['double-entry.enabled' => false]);
    }

    private function userWith(array $permissions, string $roleName): User
    {
        $user = User::forceCreate([
            'id'       => max(2, (int) User::max('id') + 1),
            'name'     => $roleName.' user',
            'email'    => uniqid($roleName).'@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'admin']);
        $role->syncPermissions(array_map(
            fn (string $name) => Permission::where('name', $name)->where('guard_name', 'admin')->firstOrFail(),
            $permissions,
        ));
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles');

        return $user;
    }

    private function fund(string $direction, float $amount, string $source = 'manual_add'): void
    {
        FundTransaction::create([
            'direction' => $direction,
            'source' => $source,
            'amount' => $amount,
            'note' => 'test',
            'created_by' => 1,
        ]);
    }

    public function test_staff_without_fund_permission_is_refused(): void
    {
        $this->actingAs($this->userWith([], 'staff'), 'admin');

        $this->get(route('admin.accounts.reports.index'))->assertForbidden();
        $this->get(route('admin.accounts.reports.show', 'cash-bank-balance'))->assertForbidden();
    }

    public function test_every_built_report_opens_for_an_admin(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get(route('admin.accounts.reports.index'))->assertOk();

        foreach (['cash-bank-balance', 'customer-balance', 'supplier-balance', 'capital-balance',
            'general-expense-balance', 'daily-statement', 'cash-bank-ledger', 'daily-ledger',
            'capital-ledger', 'general-expense-ledger', 'balance-sheet', 'product-profit-loss', 'party-profit-loss', 'expense-record', 'receipt-record', 'payment-record', 'product-ledger', 'employee-expense-advance-balance', 'employee-expense-advance-ledger', 'employee-salary-advance-ledger'] as $slug) {
            $this->get(route('admin.accounts.reports.show', $slug))->assertOk();
        }
    }

    public function test_linked_reports_redirect_to_their_existing_pages(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get(route('admin.accounts.reports.show', 'net-profit-loss'))
            ->assertRedirect(route('admin.reports.profit_loss'));
        $this->get(route('admin.accounts.reports.show', 'customer-ledger'))
            ->assertRedirect(route('admin.accounts.customers'));
    }

    public function test_reports_not_yet_built_are_shown_as_such_and_unknown_slugs_are_404(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get(route('admin.accounts.reports.show', 'challan-report'))
            ->assertOk()
            ->assertSee('not built yet');

        $this->get(route('admin.accounts.reports.show', 'no-such-report'))->assertNotFound();
    }

    public function test_cash_balance_is_money_in_less_money_out_from_the_funds_ledger(): void
    {
        $this->fund('in', 1000);
        $this->fund('in', 500);
        $this->fund('out', 300);

        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get(route('admin.accounts.reports.show', 'cash-bank-balance'))
            ->assertOk()
            ->assertSee('1,200.00');
    }

    public function test_daily_statement_and_cash_ledger_list_the_period_rows(): void
    {
        $this->fund('in', 250);
        $this->fund('out', 40);

        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get(route('admin.accounts.reports.show', 'cash-bank-ledger'))
            ->assertOk()
            ->assertSee('Opening balance')
            ->assertSee('210.00');
    }

    public function test_balance_sheet_shows_unexplained_difference_rather_than_forcing_it(): void
    {
        $this->fund('in', 1000);

        $this->actingAs($this->userWith([], 'admin'), 'admin');

        // Only cash exists, nothing owed or invested: the whole cash is the difference.
        $this->get(route('admin.accounts.reports.show', 'balance-sheet'))
            ->assertOk()
            ->assertSee('Difference of 1,000.00');
    }

    public function test_expense_record_lists_expenses_in_the_period(): void
    {
        \App\Models\Expense::create([
            'title' => 'Office Decoration', 'amount' => 450, 'expense_date' => now()->toDateString(),
            'category' => 'Administrative Expense', 'created_by' => 1,
        ]);

        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get(route('admin.accounts.reports.show', 'expense-record'))
            ->assertOk()
            ->assertSee('Office Decoration')
            ->assertSee('450.00');
    }
}
