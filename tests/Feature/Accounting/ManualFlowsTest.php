<?php

namespace Tests\Feature\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\User;
use App\Services\Accounting\ManualEntryService;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\ReversalService;
use Softmit\DoubleEntry\Support\AccountRole;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The manually operated money screens: fund add / withdraw, and expenses.
 *
 * What these tests insist on is that the money record and its journal agree —
 * same amount, same direction, exactly one of each, and a reason recorded for
 * every case where they cannot.
 */
class ManualFlowsTest extends AccountingTestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Owner',
            'email'    => 'owner@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $this->admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin->unsetRelation('roles');

        $this->actingAs($this->admin, 'admin');
    }

    public function test_money_in_recorded_as_owner_capital_debits_cash_and_credits_equity(): void
    {
        $this->post(route('admin.fund.add'), [
            'amount' => 5000,
            'note'   => 'Owner put money in',
            'nature' => 'owner_capital',
        ])->assertRedirect();

        $journal = $this->journalFor(SourceType::OWNER_CAPITAL, $this->latestFundId());

        $this->assertTrue($journal->isBalanced());
        $this->assertLine($journal, AccountRole::CASH, 'debit', '5000.00');
        $this->assertLine($journal, AccountRole::OWNER_CAPITAL, 'credit', '5000.00');
        $this->assertSame(2, $journal->lines->count(), 'a capital injection touches exactly two accounts');
    }

    public function test_money_in_recorded_as_income_credits_an_income_account_not_equity(): void
    {
        $this->post(route('admin.fund.add'), [
            'amount' => 1200.50,
            'note'   => 'Old stock sold',
            'nature' => 'other_income',
        ])->assertRedirect();

        $journal = $this->journalFor(SourceType::INCOME, $this->latestFundId());

        $this->assertLine($journal, AccountRole::CASH, 'debit', '1200.50');
        $this->assertLine($journal, AccountRole::OTHER_INCOME, 'credit', '1200.50');
    }

    public function test_money_in_without_a_nature_is_refused_before_anything_is_saved(): void
    {
        $this->post(route('admin.fund.add'), ['amount' => 500, 'note' => '?'])
            ->assertSessionHasErrors('nature');

        $this->assertDatabaseCount('fund_transactions', 0);
        $this->assertDatabaseCount('accounting_journal_entries', 0);
    }

    public function test_withdrawal_is_the_owner_taking_money_out_and_not_an_expense(): void
    {
        $this->fundIn(5000);

        $this->post(route('admin.fund.withdraw'), ['amount' => 900, 'note' => 'Personal use'])
            ->assertRedirect();

        $journal = $this->journalFor(SourceType::OWNER_WITHDRAWAL, $this->latestFundId());

        $this->assertLine($journal, AccountRole::OWNER_DRAWINGS, 'debit', '900.00');
        $this->assertLine($journal, AccountRole::CASH, 'credit', '900.00');

        $expense_roles = [AccountRole::GENERAL_EXPENSE, AccountRole::RENT_EXPENSE, AccountRole::SALARY_EXPENSE];
        $this->assertFalse(
            $journal->lines->contains(fn ($line) => in_array($line->account->role, $expense_roles, true)),
            'drawings must not reduce profit'
        );
    }

    public function test_an_expense_reaches_the_account_its_category_names(): void
    {
        $this->fundIn(5000);

        $this->post(route('admin.expenses.store'), [
            'title'        => 'Office rent',
            'amount'       => 800,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => 'rent',
            'note'         => 'September',
        ])->assertRedirect();

        $journal = $this->journalFor(SourceType::EXPENSE, Expense::latest('id')->value('id'));

        $this->assertTrue($journal->isBalanced());
        $this->assertLine($journal, AccountRole::RENT_EXPENSE, 'debit', '800.00');
        $this->assertLine($journal, AccountRole::CASH, 'credit', '800.00');
    }

    public function test_an_unmapped_category_still_lands_on_a_real_expense_account(): void
    {
        $this->fundIn(5000);

        $this->post(route('admin.expenses.store'), [
            'title'        => 'Random stuff',
            'amount'       => 150,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => 'miscellaneous!!',
        ])->assertRedirect();

        $journal = $this->journalFor(SourceType::EXPENSE, Expense::latest('id')->value('id'));

        $this->assertLine($journal, AccountRole::GENERAL_EXPENSE, 'debit', '150.00');
    }

    public function test_saving_the_same_row_twice_cannot_produce_a_second_journal(): void
    {
        $expense = $this->makeExpense('Courier', 120, 'delivery');

        $first = app(ManualEntryService::class)->expense($expense);
        $again = app(ManualEntryService::class)->expense($expense->fresh());

        $this->assertTrue($first->isPosted());
        $this->assertSame($first->journal->id, $again->journal->id);
        $this->assertSame(
            1,
            JournalEntry::where('source_type', SourceType::EXPENSE->value)
                ->where('source_id', $expense->id)
                ->count(),
            'one expense must never be booked twice'
        );
    }

    public function test_the_journal_carries_the_source_the_actor_and_the_reference(): void
    {
        $this->fundIn(5000);

        $this->post(route('admin.expenses.store'), [
            'title'        => 'Electricity',
            'amount'       => 300,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => 'utility',
            'note'         => 'bill for August',
        ]);

        $expense = Expense::latest('id')->firstOrFail();
        $journal = $this->journalFor(SourceType::EXPENSE, (int) $expense->id);

        // SOURCE: which row produced this journal, and it must be navigable back.
        $this->assertSame(SourceType::EXPENSE->value, $journal->source_type->value);
        $this->assertSame((int) $expense->id, (int) $journal->source_id);
        // ACTOR: who moved the money, from the money record rather than the session.
        $this->assertSame($this->admin->id, (int) $journal->created_by);
        $this->assertSame($this->admin->id, (int) $journal->posted_by);
        // The screen's own words, so a journal can be read without opening the DB.
        $this->assertSame('Electricity', $journal->reference);
        $this->assertStringContainsString('bill for August', $journal->description);
    }

    public function test_a_row_dated_before_the_cutover_is_saved_and_left_out_of_the_books(): void
    {
        config(['double-entry.cutover_date' => now()->addYear()->format('Y-m-d')]);

        $this->post(route('admin.fund.add'), ['amount' => 400, 'nature' => 'owner_capital'])
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('fund_transactions', 1);
        $this->assertDatabaseCount('accounting_journal_entries', 0);
        // Not a defect, so it must not pollute the failure queue the audit reads.
        $this->assertDatabaseCount('accounting_posting_failures', 0);
    }

    public function test_a_journal_that_cannot_be_posted_is_written_down_instead_of_silently_lost(): void
    {
        config(['double-entry.manual.capital_role' => 'role_that_does_not_exist']);
        $this->registry()->flush();

        $this->post(route('admin.fund.add'), ['amount' => 400, 'nature' => 'owner_capital'])
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('fund_transactions', 1);
        $this->assertDatabaseCount('accounting_journal_entries', 0);

        $failure = DB::table('accounting_posting_failures')->first();
        $this->assertNotNull($failure, 'the books must record that they failed');
        $this->assertSame(SourceType::OWNER_CAPITAL->value, $failure->source_type);
    }

    public function test_a_posted_record_cannot_be_edited_or_deleted_from_the_money_screen(): void
    {
        $this->post(route('admin.fund.add'), ['amount' => 700, 'note' => 'Capital', 'nature' => 'owner_capital']);
        $id = $this->latestFundId();

        $this->get(route('admin.fund.edit', $id))
            ->assertRedirect(route('admin.fund.index'))
            ->assertSessionHas('error');

        $this->post(route('admin.fund.update', $id), [
            'amount' => 1, 'note' => 'tampered', 'direction' => 'in', 'nature' => 'owner_capital',
        ])->assertSessionHas('error');

        $this->delete(route('admin.fund.destroy', $id))->assertSessionHas('error');

        $this->assertDatabaseHas('fund_transactions', ['id' => $id, 'amount' => '700.00']);
        $this->assertDatabaseHas('accounting_journal_entries', ['source_type' => SourceType::OWNER_CAPITAL->value, 'source_id' => $id]);
    }

    public function test_a_reversed_journal_unlocks_the_row_and_the_correction_is_numbered_apart(): void
    {
        $this->post(route('admin.fund.add'), ['amount' => 700, 'note' => 'Capital', 'nature' => 'owner_capital']);
        $id = $this->latestFundId();
        $original = $this->journalFor(SourceType::OWNER_CAPITAL, $id);

        app(ReversalService::class)->reverse($original, 'Entered the wrong figure', $this->admin->id);

        $this->post(route('admin.fund.update', $id), [
            'amount' => 650, 'note' => 'Capital (corrected)', 'direction' => 'in', 'nature' => 'owner_capital',
        ])->assertSessionHas('success');

        $correction = $this->journalFor(SourceType::OWNER_CAPITAL, $id);

        $this->assertNotSame($original->id, $correction->id);
        $this->assertSame("owner_capital:{$id}:v2", $correction->posting_key);
        $this->assertLine($correction, AccountRole::CASH, 'debit', '650.00');
        // The mistake stays visible: reversed, not erased.
        $this->assertTrue($original->fresh()->isReversed());
    }

    public function test_a_record_that_has_been_reversed_cannot_be_deleted_either(): void
    {
        $this->post(route('admin.fund.add'), ['amount' => 700, 'nature' => 'owner_capital']);
        $id = $this->latestFundId();
        app(ReversalService::class)->reverse(
            $this->journalFor(SourceType::OWNER_CAPITAL, $id),
            'Duplicate entry',
            $this->admin->id
        );

        $this->delete(route('admin.fund.destroy', $id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('fund_transactions', ['id' => $id]);
    }

    public function test_a_fund_record_cannot_be_moved_to_the_other_side_of_the_till(): void
    {
        $this->post(route('admin.fund.add'), ['amount' => 700, 'nature' => 'other_income']);
        $id = $this->latestFundId();
        // Reverse it so the edit is otherwise allowed.
        app(ReversalService::class)->reverse(
            $this->journalFor(SourceType::INCOME, $id),
            'Re-entering it',
            $this->admin->id
        );

        $this->post(route('admin.fund.update', $id), [
            'amount' => 700, 'direction' => 'out', 'nature' => 'other_income',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('fund_transactions', ['id' => $id, 'direction' => 'in']);
    }

    public function test_the_fund_list_shows_which_nature_a_money_in_row_was_booked_as(): void
    {
        $this->post(route('admin.fund.add'), ['amount' => 700, 'note' => 'Capital', 'nature' => 'owner_capital']);
        $this->post(route('admin.fund.add'), ['amount' => 300, 'note' => 'Scrap sale', 'nature' => 'other_income']);

        $this->get(route('admin.fund.index'))
            ->assertOk()
            ->assertSee('Owner capital')
            ->assertSee('Income');
    }

    public function test_an_expense_screen_refuses_to_change_a_posted_amount(): void
    {
        $expense = $this->makeExpense('Rent', 800, 'rent');

        $this->post(route('admin.expenses.update', $expense->id), [
            'title'        => 'Rent',
            'amount'       => 8000,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => 'rent',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => '800.00']);

        $this->delete(route('admin.expenses.destroy', $expense->id))->assertSessionHas('error');
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    private function fundIn(float $amount): FundTransaction
    {
        $this->post(route('admin.fund.add'), ['amount' => $amount, 'nature' => 'owner_capital']);

        return FundTransaction::latest('id')->firstOrFail();
    }

    private function makeExpense(string $title, float $amount, ?string $category): Expense
    {
        $this->fundIn($amount + 1000);

        $this->post(route('admin.expenses.store'), [
            'title'        => $title,
            'amount'       => $amount,
            'expense_date' => now()->format('Y-m-d'),
            'category'     => $category,
        ]);

        return Expense::latest('id')->firstOrFail();
    }

    private function latestFundId(): int
    {
        return (int) FundTransaction::latest('id')->value('id');
    }

    private function journalFor(SourceType $type, int $sourceId): JournalEntry
    {
        return JournalEntry::with('lines.account')
            ->where('source_type', $type->value)
            ->where('source_id', $sourceId)
            ->latest('id')
            ->firstOrFail();
    }

    private function assertLine(JournalEntry $journal, string $role, string $side, string $amount): void
    {
        $line = $journal->lines->first(fn ($l) => $l->account->role === $role);
        $this->assertNotNull($line, "journal {$journal->journal_no} has no line for {$role}");

        $opposite = $side === 'debit' ? 'credit' : 'debit';

        $this->assertSame($amount, (string) $line->{$side});
        $this->assertSame('0.00', (string) $line->{$opposite}, "{$role} should only move on the {$side} side");
    }
}
