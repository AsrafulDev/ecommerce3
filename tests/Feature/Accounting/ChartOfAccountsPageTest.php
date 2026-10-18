<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Database\Seeders\PermissionTableSeeder;
use Softmit\DoubleEntry\Models\Account;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ChartOfAccountsPageTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionTableSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::create(['name' => 'Viewer', 'email' => 'chart@test.local', 'password' => bcrypt('secret'), 'status' => 1]);
        $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'admin']);
        $role->syncPermissions([Permission::where('name', 'accounting-list')->where('guard_name', 'admin')->firstOrFail()]);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles');

        $this->actingAs($user, 'admin');
    }

    public function test_the_chart_lists_every_account_under_its_type(): void
    {
        $account = Account::where('is_active', true)->orderBy('code')->firstOrFail();

        $this->get(route('admin.accounting.chart.index'))
            ->assertOk()
            ->assertSee($account->code)
            ->assertSee($account->name);
    }

    public function test_a_child_account_is_nested_under_its_parent(): void
    {
        $parent = Account::create(['code' => '9000', 'name' => 'Bank Accounts Group', 'account_type' => 'asset', 'normal_balance' => 'debit', 'is_active' => true]);
        $child = Account::create(['code' => '9001', 'name' => 'Dutch Bangla Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_id' => $parent->id, 'is_active' => true]);

        $html = $this->get(route('admin.accounting.chart.index'))->assertOk()->getContent();

        // The child renders after its parent, indented one level deeper.
        $this->assertLessThan(strpos($html, $child->code), strpos($html, $parent->code));
        $this->assertStringContainsString('padding-left: 20px', $html);
    }

    public function test_an_account_can_be_added_without_an_opening_balance(): void
    {
        $this->actingAs(User::where('email', 'chart@test.local')->firstOrFail(), 'admin');
        // The viewer role only holds accounting-list; grant create for this test.
        $role = Role::where('name', 'viewer')->where('guard_name', 'admin')->firstOrFail();
        $role->givePermissionTo(Permission::where('name', 'accounting-create')->where('guard_name', 'admin')->firstOrFail());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->post(route('admin.accounting.chart.store'), [
            'code' => '9100', 'name' => 'Petty Cash', 'account_type' => 'asset', 'normal_balance' => 'debit',
        ])->assertSessionHas('success');

        $account = Account::where('code', '9100')->firstOrFail();
        $this->assertSame('Petty Cash', $account->name);
        $this->assertSame(0, $account->lines()->count());
    }

    public function test_a_duplicate_account_code_is_refused(): void
    {
        $this->actingAs(User::where('email', 'chart@test.local')->firstOrFail(), 'admin');
        $role = Role::where('name', 'viewer')->where('guard_name', 'admin')->firstOrFail();
        $role->givePermissionTo(Permission::where('name', 'accounting-create')->where('guard_name', 'admin')->firstOrFail());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $existing = Account::where('is_active', true)->firstOrFail();

        $this->post(route('admin.accounting.chart.store'), [
            'code' => $existing->code, 'name' => 'Clash', 'account_type' => 'asset', 'normal_balance' => 'debit',
        ])->assertSessionHasErrors('code');
    }
}
