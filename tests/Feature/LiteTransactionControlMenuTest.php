<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Transaction Control lives in the Transactions menu. It must appear exactly
 * once whether Advanced Accounting is on or off, and must not depend on it.
 */
class LiteTransactionControlMenuTest extends TestCase
{
    use RefreshDatabase;

    private function purgeOnlyUser(): User
    {
        // Never id 1: the Gate override treats id 1 as super admin.
        $user = User::forceCreate([
            'id'       => max(2, (int) User::max('id') + 1),
            'name'     => 'Control user',
            'email'    => uniqid('control').'@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'control', 'guard_name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'purge-financial-transactions', 'guard_name' => 'admin']);
        $role->syncPermissions([$permission]);
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles');

        return $user;
    }

    private function assertMenuLinkShownOnce(): void
    {
        $url = route('admin.transaction-control.index');
        $response = $this->actingAs($this->purgeOnlyUser(), 'admin')->get($url);

        $response->assertOk();
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'href="'.$url.'"'),
            'Transaction Control must appear exactly once in the sidebar'
        );
    }

    public function test_transaction_control_menu_link_when_advanced_accounting_is_disabled(): void
    {
        config(['double-entry.enabled' => false]);

        $this->assertMenuLinkShownOnce();
    }

    public function test_transaction_control_menu_link_when_advanced_accounting_is_enabled(): void
    {
        config(['double-entry.enabled' => true]);

        $this->assertMenuLinkShownOnce();
    }
}
