<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Database\Seeders\PermissionTableSeeder;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Database\Seeders\ChartOfAccountsSeeder;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Services\AccountingDefaults;
use Softmit\DoubleEntry\Support\AccountRole;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The one-click "Sync Defaults" button has to be safe to press on a live book:
 * it may only ever ADD what is missing, never rewrite what is already there.
 * These tests pin that promise down — idempotency, repair of a deleted role
 * account, re-mapping a wiped fund, and the crucial non-destructive case where
 * an accountant has renamed a default account.
 */
class DefaultsSyncTest extends AccountingTestCase
{
    protected function defaults(): AccountingDefaults
    {
        return app(AccountingDefaults::class);
    }

    public function test_sync_is_idempotent_on_a_freshly_seeded_chart(): void
    {
        // AccountingTestCase already installed the chart exactly as production does.
        $first = $this->defaults()->sync();

        $this->assertSame(0, $first['created']);
        $this->assertTrue($first['fund_mapped']);
        $this->assertSame([], $first['missing_roles']);

        $count = Account::count();

        $second = $this->defaults()->sync();

        $this->assertSame(0, $second['created']);
        $this->assertSame($count, Account::count(), 'A second click must not duplicate accounts.');
    }

    public function test_sync_recreates_a_deleted_role_account(): void
    {
        Account::where('role', AccountRole::CASH)->forceDelete();
        $this->registry()->flush();

        $this->assertContains(AccountRole::CASH, $this->registry()->missingRoles());

        $result = $this->defaults()->sync();

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['missing_roles']);
        $this->assertTrue(
            Account::where('role', AccountRole::CASH)->first()->is_active,
            'A repaired account must be active so posting resolves it.'
        );
    }

    public function test_sync_remaps_a_wiped_default_fund(): void
    {
        DB::table('accounting_fund_accounts')->where('fund_key', 'default')->delete();

        $result = $this->defaults()->sync();

        $this->assertTrue($result['fund_mapped']);

        $mapped = DB::table('accounting_fund_accounts')
            ->where('fund_key', 'default')
            ->value('account_id');

        $this->assertSame(
            $this->accountId(AccountRole::CASH),
            (int) $mapped,
            'The default fund must point at the cash account.'
        );
    }

    public function test_sync_never_overwrites_a_renamed_default_account(): void
    {
        $account = Account::where('role', AccountRole::GENERAL_EXPENSE)->firstOrFail();

        $account->update(['name' => 'Sundry Operating Costs']);

        $result = $this->defaults()->sync();

        $this->assertSame(0, $result['created']);
        $this->assertSame(
            'Sundry Operating Costs',
            $account->fresh()->name,
            'Sync adds defaults; it does not undo an accountant\'s edits.'
        );
    }

    public function test_sync_adds_the_standard_manual_chart_accounts_and_is_idempotent(): void
    {
        $manual = array_filter(
            (new ChartOfAccountsSeeder())->accounts(),
            fn ($account) => $account['role'] === null
        );
        $this->assertNotEmpty($manual, 'The chart is expected to ship manual (role-less) accounts.');

        // Strip every role-less account, leaving a role-only chart.
        DB::table('accounting_accounts')->whereNull('role')->delete();
        $this->registry()->flush();

        $result = $this->defaults()->sync();

        $this->assertSame(count($manual), $result['created'], 'Sync must create every missing manual account.');

        $petty = DB::table('accounting_accounts')->where('code', '1030')->first();
        $this->assertNotNull($petty);
        $this->assertNull($petty->role, 'A manual account carries no posting role.');
        $this->assertFalse((bool) $petty->is_system, 'A manual account is freely editable, not a system contract.');

        // A second click must not duplicate or collide on the codes it just added.
        $second = $this->defaults()->sync();
        $this->assertSame(0, $second['created']);
        $this->assertSame([], $second['missing_roles'], 'A manual account must never be counted as a missing role.');
    }

    public function test_sync_leaves_a_renamed_manual_account_untouched(): void
    {
        DB::table('accounting_accounts')->whereNull('role')->delete();
        $this->defaults()->sync();

        $account = Account::where('code', '6700')->firstOrFail(); // Advertising & Marketing
        $account->update(['name' => 'Marketing Spend']);

        $result = $this->defaults()->sync();

        $this->assertSame(0, $result['created']);
        $this->assertSame(
            'Marketing Spend',
            $account->fresh()->name,
            'A manual account the bookkeeper renamed stays as they left it.'
        );
    }

    public function test_the_sync_route_requires_the_create_permission(): void
    {
        $this->seed(PermissionTableSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $route = route('admin.accounting.accounts.sync-defaults');

        $make = function (string $email, string $roleName, array $permissions): User {
            $user = User::create([
                'name' => $email, 'email' => $email,
                'password' => bcrypt('secret'), 'status' => 1,
            ]);
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'admin']);
            $role->syncPermissions($permissions);
            $user->assignRole($role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user;
        };

        // Warm the permission gate with a full, working request first.
        $writer = $make('writer@test.local', 'account-writer', ['accounting-list', 'accounting-create']);
        $this->actingAs($writer, 'admin')->get(route('admin.accounting.accounts.index'))->assertOk();

        // With accounting-create the click succeeds and returns to the chart.
        $this->post($route)
            ->assertRedirect(route('admin.accounting.accounts.index'))
            ->assertSessionHasNoErrors();

        // Listing accounts is not creating them: the write is refused.
        $reader = $make('reader@test.local', 'account-reader', ['accounting-list']);
        $this->actingAs($reader, 'admin')->post($route)->assertForbidden();
    }
}
