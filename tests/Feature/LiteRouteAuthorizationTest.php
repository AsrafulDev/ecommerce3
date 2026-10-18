<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Route-level authorization for the Lite (Core) Transactions screens.
 *
 * Each route must enforce the same permission its sidebar item already checks.
 * Admin-role users keep full access through the Gate override; a staff role
 * with no matching permission is refused with 403 even when it types the URL.
 */
class LiteRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['double-entry.enabled' => false]);
    }

    /** Admin-guard user holding the given permission names through a staff role. */
    private function userWith(array $permissions, string $roleName = 'staff'): User
    {
        // Never user id 1: the Gate override treats id 1 as super admin, which would grant everything.
        $user = User::forceCreate([
            'id'       => max(2, (int) User::max('id') + 1),
            'name'     => $roleName.' user',
            'email'    => uniqid($roleName).'@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);

        // Permission rows come from the provisioning migration; a missing row fails loudly here.
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

    private function adminRoleUser(): User
    {
        return $this->userWith([], 'admin');
    }

    public function test_staff_without_lite_permissions_is_refused_on_every_transactions_screen(): void
    {
        $this->actingAs($this->userWith([]), 'admin');
        foreach ([
            'admin.fund.index',
            'admin.fund.logs',
            'admin.transactions.create',
            'admin.expenses.index',
            'admin.expenses.logs',
            'admin.accounts.dashboard',
            'admin.accounts.customers',
            'admin.accounts.suppliers',
            'admin.reports.orders',
            'admin.reports.purchases',
            'admin.reports.expenses',
            'admin.reports.stock',
            'admin.reports.profit_loss',
            'admin.transaction-control.index',
        ] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_role_keeps_full_access_to_transactions_screens(): void
    {
        $this->actingAs($this->adminRoleUser(), 'admin');

        foreach ([
            'admin.fund.index',
            'admin.expenses.index',
            'admin.accounts.dashboard',
            'admin.accounts.customers',
            'admin.reports.profit_loss',
            'admin.transaction-control.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_expense_list_grants_only_the_expense_register(): void
    {
        $this->actingAs($this->userWith(['expense-list']), 'admin');

        $this->get(route('admin.expenses.index'))->assertOk();
        $this->get(route('admin.fund.index'))->assertForbidden();
        // Viewing is not creating: a list-only user cannot post a new expense.
        $this->post(route('admin.expenses.store'), [])->assertForbidden();
    }

    public function test_fund_list_allows_viewing_but_not_money_movement(): void
    {
        $this->actingAs($this->userWith(['fund-list']), 'admin');

        $this->get(route('admin.fund.logs'))->assertOk();
        $this->post(route('admin.fund.add'), ['amount' => 100, 'note' => 'x', 'nature' => 'owner_capital'])
            ->assertForbidden();
        $this->post(route('admin.fund.withdraw'), ['amount' => 100, 'note' => 'x'])
            ->assertForbidden();
    }

    public function test_report_view_opens_the_operational_reports(): void
    {
        $this->actingAs($this->userWith(['report-view']), 'admin');

        $this->get(route('admin.reports.orders'))->assertOk();
        $this->get(route('admin.reports.profit_loss'))->assertOk();
        $this->get(route('admin.fund.index'))->assertForbidden();
    }

    public function test_purge_permission_alone_reaches_transaction_control_but_not_balances(): void
    {
        // Seeded by PermissionTableSeeder in real installs; tests do not run seeders.
        Permission::firstOrCreate(['name' => 'purge-financial-transactions', 'guard_name' => 'admin']);

        $this->actingAs($this->userWith(['purge-financial-transactions']), 'admin');

        $this->get(route('admin.transaction-control.index'))->assertOk();
        // Overview and balances are not part of the purge permission.
        $this->get(route('admin.accounts.dashboard'))->assertForbidden();
        $this->get(route('admin.accounts.customers'))->assertForbidden();
    }
}
