<?php

namespace Tests\Feature\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\User;
use App\Services\Accounting\ManualEntryService;
use Database\Seeders\PermissionTableSeeder;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Lite → double-entry bridge.
 *
 * "Send data" must be safe to click twice and must never decide something the
 * operator has not said; "reset" must be reversal-only and must never reach
 * outside the four Lite source types. These tests pin exactly those promises.
 */
class LiteSyncTest extends AccountingTestCase
{
    protected function service(): ManualEntryService
    {
        return app(ManualEntryService::class);
    }

    protected function expense(float $amount = 100.0, string $category = 'Rent'): Expense
    {
        return Expense::create([
            'title'        => 'Office rent',
            'category'     => $category,
            'amount'       => $amount,
            'expense_date' => '2024-05-01',
            'created_by'   => 1,
        ]);
    }

    protected function moneyIn(float $amount = 70.0): FundTransaction
    {
        return FundTransaction::create([
            'direction'  => 'in',
            'source'     => 'manual_add',
            'source_id'  => null,
            'amount'     => $amount,
            'note'       => 'Owner top-up',
            'created_by' => 1,
        ]);
    }

    protected function withdrawal(float $amount = 50.0): FundTransaction
    {
        return FundTransaction::create([
            'direction'  => 'out',
            'source'     => 'withdraw',
            'source_id'  => null,
            'amount'     => $amount,
            'note'       => 'Cash out',
            'created_by' => 1,
        ]);
    }

    protected function liteJournals(SourceType $type, int $sourceId)
    {
        return JournalEntry::query()
            ->where('source_type', $type->value)
            ->where('source_id', $sourceId)
            ->get();
    }

    public function test_pending_lists_only_rows_without_a_standing_journal(): void
    {
        $expense = $this->expense();

        $pending = $this->service()->pending();
        $this->assertTrue($pending['expenses']->contains('id', $expense->id));

        // Once the single-row writer posts it, the row is no longer owed.
        $this->service()->expense($expense);

        $this->assertFalse($this->service()->pending()['expenses']->contains('id', $expense->id));
    }

    public function test_sync_all_posts_every_kind_once_and_is_idempotent(): void
    {
        $expense = $this->expense();
        $withdraw = $this->withdrawal();
        $moneyIn = $this->moneyIn();

        $result = $this->service()->syncAll('owner_capital');

        $this->assertSame(3, $result['posted']);
        $this->assertSame(0, $result['failed']);

        $this->assertSame(JournalStatus::POSTED, $this->liteJournals(SourceType::EXPENSE, $expense->id)->first()->status);
        $this->assertSame(JournalStatus::POSTED, $this->liteJournals(SourceType::OWNER_WITHDRAWAL, $withdraw->id)->first()->status);
        $this->assertSame(JournalStatus::POSTED, $this->liteJournals(SourceType::OWNER_CAPITAL, $moneyIn->id)->first()->status);

        // A second click finds nothing owed: the idempotent keys already stand.
        $again = $this->service()->syncAll('owner_capital');
        $this->assertSame(0, $again['posted']);
        $this->assertSame(1, $this->liteJournals(SourceType::EXPENSE, $expense->id)->count());
    }

    public function test_money_in_without_a_chosen_nature_is_left_for_the_operator(): void
    {
        $moneyIn = $this->moneyIn();

        // Refuses to guess between equity and profit.
        $skipped = $this->service()->syncAll(null);
        $this->assertSame(1, $skipped['needs_nature']);
        $this->assertSame(0, $skipped['posted']);
        $this->assertSame(0, $this->liteJournals(SourceType::OWNER_CAPITAL, $moneyIn->id)->count());

        // With an explicit nature it posts, as income this time.
        $posted = $this->service()->syncAll('other_income');
        $this->assertSame(1, $posted['posted']);
        $this->assertSame(JournalStatus::POSTED, $this->liteJournals(SourceType::INCOME, $moneyIn->id)->first()->status);
    }

    public function test_reset_reopens_lite_journals_and_leaves_everything_else_alone(): void
    {
        $expense = $this->expense();
        $this->service()->syncAll('owner_capital');
        $expenseJournal = $this->liteJournals(SourceType::EXPENSE, $expense->id)->first();

        // A non-Lite journal (an opening post, say) must survive the reset untouched.
        $opening = app(JournalPoster::class)->post(
            JournalDraft::make('2024-01-01')
                ->from(SourceType::OPENING, null, 'opening:test')
                ->key('opening:test')
                ->debit(AccountRole::CASH, '500.00')
                ->credit(AccountRole::OWNER_CAPITAL, '500.00')
        );

        $result = $this->service()->resetLiteJournals('Re-open for re-sync', 1);

        $this->assertSame(1, $result['reversed']);
        $this->assertSame(JournalStatus::REVERSED, $expenseJournal->fresh()->status);
        $this->assertSame(JournalStatus::POSTED, $opening->fresh()->status);

        // The row is owed a corrected entry again, so it reappears in pending.
        $this->assertTrue($this->service()->pending()['expenses']->contains('id', $expense->id));

        // Nothing standing is left to reverse.
        $this->assertSame(0, $this->service()->resetLiteJournals('again', 1)['reversed']);
    }

    public function test_the_sync_screen_renders_for_a_bookkeeper(): void
    {
        $this->actingAs($this->authedAdmin('sync-view@test.local', ['accounting-list']));

        $this->get(route('admin.accounting.sync.index'))->assertOk();
    }

    public function test_the_run_and_reset_actions_are_permission_gated(): void
    {
        // Warm the gate with a full-privilege request first, then prove a reader
        // cannot post or reverse.
        $admin = $this->authedAdmin('sync-run@test.local', ['accounting-list', 'accounting-create', 'accounting-reverse']);
        $this->get(route('admin.accounting.sync.index'))->assertOk();

        $reader = $this->authedAdmin('sync-reader@test.local', ['accounting-list']);
        $this->post(route('admin.accounting.sync.run'))->assertForbidden();
        $this->post(route('admin.accounting.sync.reset'), ['confirm' => 'RE-OPEN'])->assertForbidden();
    }

    public function test_reset_refuses_a_confirmation_that_is_not_the_exact_phrase(): void
    {
        $expense = $this->expense();
        $this->service()->syncAll('owner_capital');

        $this->authedAdmin('sync-reset@test.local', ['accounting-list', 'accounting-create', 'accounting-reverse']);

        $this->post(route('admin.accounting.sync.reset'), ['confirm' => 'reset'])
            ->assertRedirect(route('admin.accounting.sync.index'))
            ->assertSessionHasNoErrors();

        // The guard mis-fired: the journal is still standing, nothing reversed.
        $this->assertSame(JournalStatus::POSTED, $this->liteJournals(SourceType::EXPENSE, $expense->id)->first()->status);
    }

    /**
     * Build an admin-guard user with a purpose-made role and log it in, warming
     * Spatie's permission gate the same way the working UI tests do.
     *
     * @param  array<int, string>  $permissions
     */
    protected function authedAdmin(string $email, array $permissions): User
    {
        // RefreshDatabase truncates every test, so re-seed whenever the rows are
        // gone — but only once per test, since a re-seed would hit the uniques.
        if (!Permission::where('name', 'accounting-list')->where('guard_name', 'admin')->exists()) {
            $this->seed(PermissionTableSeeder::class);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::create([
            'name' => $email, 'email' => $email,
            'password' => bcrypt('secret'), 'status' => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'sync-role', 'guard_name' => 'admin']);
        $role->syncPermissions($permissions);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user, 'admin');

        return $user;
    }
}
