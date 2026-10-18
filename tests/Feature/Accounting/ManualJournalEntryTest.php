<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Database\Seeders\PermissionTableSeeder;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Support\AccountRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Manual journal entry: a balanced journal posts once; an unbalanced one posts
 * nothing. Only meaningful with the advanced engine on.
 */
class ManualJournalEntryTest extends AccountingTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionTableSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::create(['name' => 'Bookkeeper', 'email' => 'journal@test.local', 'password' => bcrypt('secret'), 'status' => 1]);
        $role = Role::firstOrCreate(['name' => 'bookkeeper', 'guard_name' => 'admin']);
        $role->syncPermissions(array_map(
            fn (string $name) => Permission::where('name', $name)->where('guard_name', 'admin')->firstOrFail(),
            ['accounting-list', 'accounting-create'],
        ));
        $this->admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin->unsetRelation('roles');

        $this->actingAs($this->admin, 'admin');
        config(['double-entry.cutover_date' => '2000-01-01']);
    }

    public function test_a_balanced_journal_posts_once_with_both_sides(): void
    {
        $cash = $this->accountId(AccountRole::CASH);
        $capital = $this->accountId(AccountRole::RETAINED_EARNINGS);

        $this->post(route('admin.accounting.journals.entry.store'), [
            'transaction_date' => now()->toDateString(),
            'description' => 'Owner put cash into the business',
            'lines' => [
                ['account_id' => $cash, 'debit' => '500', 'credit' => '', 'description' => 'Cash in'],
                ['account_id' => $capital, 'debit' => '', 'credit' => '500', 'description' => 'Capital'],
            ],
        ])->assertSessionHas('success');

        $this->assertSame(1, JournalEntry::count());
        $this->assertSame(1, JournalEntry::where('status', 'posted')->count());
    }

    public function test_an_unbalanced_journal_posts_nothing(): void
    {
        $cash = $this->accountId(AccountRole::CASH);
        $capital = $this->accountId(AccountRole::RETAINED_EARNINGS);

        $this->post(route('admin.accounting.journals.entry.store'), [
            'transaction_date' => now()->toDateString(),
            'description' => 'Does not balance',
            'lines' => [
                ['account_id' => $cash, 'debit' => '500', 'credit' => ''],
                ['account_id' => $capital, 'debit' => '', 'credit' => '400'],
            ],
        ])->assertSessionHas('error');

        $this->assertSame(0, JournalEntry::where('status', 'posted')->count());
    }

    public function test_a_line_with_both_a_debit_and_a_credit_is_refused(): void
    {
        $cash = $this->accountId(AccountRole::CASH);
        $capital = $this->accountId(AccountRole::RETAINED_EARNINGS);

        $this->post(route('admin.accounting.journals.entry.store'), [
            'transaction_date' => now()->toDateString(),
            'description' => 'Both sides',
            'lines' => [
                ['account_id' => $cash, 'debit' => '500', 'credit' => '500'],
                ['account_id' => $capital, 'debit' => '', 'credit' => '500'],
            ],
        ])->assertSessionHasErrors('lines.0.debit');

        $this->assertSame(0, JournalEntry::where('status', 'posted')->count());
    }
}
