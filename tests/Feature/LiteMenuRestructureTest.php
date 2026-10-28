<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sidebar visibility for the Transactions (Core) and Accounting (Advanced) menus.
 * Each link must appear at most once, must match a permission the route enforces,
 * and Transactions must never depend on Advanced Accounting.
 */
class LiteMenuRestructureTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions, string $roleName = 'staff'): User
    {
        // Never user id 1: the Gate override treats id 1 as super admin.
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

    /** Render a page the user may open, and return its HTML. */
    private function sidebarFor(User $user, string $route): string
    {
        $response = $this->actingAs($user, 'admin')->get(route($route));
        $response->assertOk();

        return $response->getContent();
    }

    /** The Transactions section of the sidebar only: from its collapse div to the end of its list. */
    private function transactionsSection(string $html): string
    {
        $start = strpos($html, 'id="sidebar-transactions"');
        $this->assertNotFalse($start, 'Transactions menu is missing');

        return substr($html, $start, strpos($html, '</ul>', $start) - $start);
    }

    /** The Accounting section of the sidebar only, or '' when it is not rendered. */
    private function accountingSection(string $html): string
    {
        $start = strpos($html, 'id="sidebar-accounting"');
        if ($start === false) {
            return '';
        }

        return substr($html, $start, strpos($html, '</ul>', $start) - $start);
    }

    public function test_core_transactions_menu_is_available_with_advanced_accounting_disabled(): void
    {
        config(['double-entry.enabled' => false]);

        $html = $this->transactionsSection($this->sidebarFor($this->userWith([], 'admin'), 'admin.accounts.dashboard'));

        foreach (['admin.accounts.dashboard', 'admin.transactions.create', 'admin.fund.index', 'admin.accounts.customers',
                  'admin.accounts.suppliers', 'admin.fund.logs', 'admin.expenses.index',
                  'admin.transaction-control.index'] as $route) {
            $url = route($route);
            $this->assertSame(1, substr_count($html, 'href="'.$url.'"'), "{$route} must appear exactly once in Transactions");
        }
    }

    public function test_accounting_menu_is_hidden_when_advanced_accounting_is_disabled(): void
    {
        config(['double-entry.enabled' => false]);

        $html = $this->sidebarFor($this->userWith([], 'admin'), 'admin.accounts.dashboard');

        $this->assertStringNotContainsString('id="sidebar-accounting"', $html);
        $this->assertStringNotContainsString(route('admin.accounting.accounts.index'), $html);
    }

    public function test_accounting_menu_lists_implemented_books_when_enabled_and_permitted(): void
    {
        config(['double-entry.enabled' => true]);

        $section = $this->accountingSection($this->sidebarFor($this->userWith([], 'admin'), 'admin.accounts.dashboard'));

        $this->assertNotSame('', $section, 'Accounting menu should be visible to an admin with books enabled');
        foreach (['admin.accounting.accounts.index', 'admin.accounting.journals.index', 'admin.accounting.ledger.balances',
                  'admin.accounting.opening.index', 'admin.accounting.sync.index'] as $route) {
            $this->assertSame(1, substr_count($section, 'href="'.route($route).'"'), "{$route} must appear once in Accounting");
        }
        // No dashboard exists for the books, so none is listed.
        $this->assertStringNotContainsString('Dashboard', $section);
    }

    public function test_reports_menu_lists_books_reports_once_when_enabled_and_permitted(): void
    {
        config(['double-entry.enabled' => true]);

        $html = $this->sidebarFor($this->userWith([], 'admin'), 'admin.accounts.dashboard');
        $start = strpos($html, 'id="sidebar-reports"');
        $this->assertNotFalse($start, 'Reports menu should be visible to an admin with books enabled');
        $section = substr($html, $start, strpos($html, '</ul>', $start) - $start);

        foreach (['admin.accounting.reports.trial-balance', 'admin.accounting.reports.profit-loss',
                  'admin.accounting.reports.cash', 'admin.reports.profit_loss'] as $route) {
            $this->assertSame(1, substr_count($section, 'href="'.route($route).'"'), "{$route} must appear once in Reports");
        }
    }

    public function test_accounting_menu_needs_accounting_permission_even_when_enabled(): void
    {
        config(['double-entry.enabled' => true]);

        $html = $this->sidebarFor($this->userWith(['expense-list']), 'admin.expenses.index');

        $this->assertStringNotContainsString('id="sidebar-accounting"', $html);
    }

    public function test_staff_see_only_the_transactions_links_their_permissions_allow(): void
    {
        config(['double-entry.enabled' => false]);

        $html = $this->transactionsSection($this->sidebarFor($this->userWith(['expense-list']), 'admin.expenses.index'));

        $this->assertStringContainsString('href="'.route('admin.expenses.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.fund.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.fund.logs').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.transaction-control.index').'"', $html);
    }

    public function test_purge_only_user_sees_transaction_control_inside_transactions(): void
    {
        config(['double-entry.enabled' => false]);
        Permission::firstOrCreate(['name' => 'purge-financial-transactions', 'guard_name' => 'admin']);

        $html = $this->transactionsSection($this->sidebarFor($this->userWith(['purge-financial-transactions']), 'admin.transaction-control.index'));

        $this->assertSame(1, substr_count($html, 'href="'.route('admin.transaction-control.index').'"'));
        $this->assertStringNotContainsString('href="'.route('admin.accounts.dashboard').'"', $html, 'Overview must stay hidden from purge-only users');
    }
}
