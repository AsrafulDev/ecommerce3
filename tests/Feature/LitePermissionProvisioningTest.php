<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Core Transactions permissions: provisioned as rows, granted to nobody by
 * default, and enforced for custom staff roles only when an operator assigns them.
 */
class LitePermissionProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const LITE = [
        'fund-list', 'fund-create', 'fund-edit',
        'expense-list', 'expense-create', 'expense-edit',
        'report-view', 'order-report', 'purchase-report', 'expense-report', 'stock-report', 'profit-loss-report',
    ];

    public function test_every_lite_permission_row_exists_for_the_admin_guard(): void
    {
        foreach (self::LITE as $name) {
            $this->assertTrue(
                DB::table('permissions')->where('name', $name)->where('guard_name', 'admin')->exists(),
                "missing permission row: {$name}"
            );
        }
    }

    public function test_migrations_grant_no_lite_permission_to_any_role(): void
    {
        $granted = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->whereIn('permissions.name', self::LITE)
            ->count();

        $this->assertSame(0, $granted, 'the provisioning migration must not assign any role');
    }

    public function test_rollback_removes_only_unassigned_rows(): void
    {
        $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('report-view');

        $migration = require database_path('migrations/2026_10_15_000001_provision_lite_transaction_permissions.php');
        $migration->down();

        $this->assertTrue(DB::table('permissions')->where('name', 'report-view')->exists(), 'assigned permission must survive rollback');
        $this->assertFalse(DB::table('permissions')->where('name', 'fund-list')->exists(), 'unassigned permission should be removed');
    }

    public function test_custom_staff_role_gets_only_what_it_is_assigned(): void
    {
        config(['double-entry.enabled' => false]);

        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'admin']);
        $cashier->givePermissionTo(['fund-list', 'fund-create']);

        $user = User::forceCreate([
            'id' => max(2, (int) User::max('id') + 1),
            'name' => 'Cashier',
            'email' => uniqid('cashier').'@test.local',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        $user->assignRole($cashier);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user, 'admin');

        $this->get(route('admin.fund.index'))->assertOk();
        $this->get(route('admin.expenses.index'))->assertForbidden();
        $this->get(route('admin.reports.profit_loss'))->assertForbidden();
        $this->post(route('admin.expenses.store'), [])->assertForbidden();
    }
}
