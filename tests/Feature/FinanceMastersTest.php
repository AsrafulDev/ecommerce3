<?php

namespace Tests\Feature;

use App\Models\FundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Finance setup lists: create, rename, delete, and the rules that protect them.
 */
class FinanceMastersTest extends TestCase
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

    public function test_staff_without_fund_permission_cannot_open_or_change_masters(): void
    {
        $this->actingAs($this->userWith([], 'staff'), 'admin');

        $this->get(route('admin.finance.masters.index', 'area'))->assertForbidden();
        $this->post(route('admin.finance.masters.store', 'area'), ['name' => 'Dhanmondi'])->assertForbidden();
        $this->assertDatabaseMissing('finance_areas', ['name' => 'Dhanmondi']);
    }

    public function test_unknown_master_type_is_not_found(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->get('/admin/finance/masters/no-such-type')->assertNotFound();
    }

    public function test_an_area_can_be_added_renamed_and_deleted(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');

        $this->post(route('admin.finance.masters.store', 'area'), ['name' => 'Dhanmondi'])
            ->assertRedirect(route('admin.finance.masters.index', 'area'));
        $this->assertDatabaseHas('finance_areas', ['name' => 'Dhanmondi']);

        $id = DB::table('finance_areas')->where('name', 'Dhanmondi')->value('id');

        $this->post(route('admin.finance.masters.update', ['area', $id]), ['name' => 'Dhanmondi 27'])
            ->assertRedirect();
        $this->assertDatabaseHas('finance_areas', ['id' => $id, 'name' => 'Dhanmondi 27']);

        $this->post(route('admin.finance.masters.destroy', ['area', $id]))->assertRedirect();
        $this->assertDatabaseMissing('finance_areas', ['id' => $id]);
    }

    public function test_a_duplicate_name_is_refused(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');
        DB::table('expense_heads')->insert(['title' => 'Office Decoration', 'created_at' => now(), 'updated_at' => now()]);

        $this->post(route('admin.finance.masters.store', 'expense-head'), ['title' => 'Office Decoration'])
            ->assertSessionHasErrors('title');

        $this->assertSame(1, DB::table('expense_heads')->where('title', 'Office Decoration')->count());
    }

    public function test_an_account_group_must_belong_to_a_real_account_category(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');
        $assets = DB::table('finance_account_categories')->where('name', 'Assets')->value('id');

        $this->post(route('admin.finance.masters.store', 'account-group'), ['name' => 'Bank Accounts', 'account_category_id' => $assets])
            ->assertRedirect();
        $this->assertDatabaseHas('finance_account_groups', ['name' => 'Bank Accounts', 'account_category_id' => $assets]);

        $this->post(route('admin.finance.masters.store', 'account-group'), ['name' => 'Ghost', 'account_category_id' => 999])
            ->assertSessionHasErrors('account_category_id');
    }

    public function test_an_account_category_in_use_by_a_group_is_not_deleted(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');
        $assets = DB::table('finance_account_categories')->where('name', 'Assets')->value('id');
        DB::table('finance_account_groups')->insert(['name' => 'Cash-In-Hand', 'account_category_id' => $assets, 'created_at' => now(), 'updated_at' => now()]);

        $this->post(route('admin.finance.masters.destroy', ['account-category', $assets]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('finance_account_categories', ['id' => $assets]);
    }

    public function test_masters_never_touch_the_funds_ledger(): void
    {
        $this->actingAs($this->userWith([], 'admin'), 'admin');
        $before = FundTransaction::count();

        $this->post(route('admin.finance.masters.store', 'expense-category'), ['name' => 'IT Expense']);

        $this->assertSame($before, FundTransaction::count());
    }
}
