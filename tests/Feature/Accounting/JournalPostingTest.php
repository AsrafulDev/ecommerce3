<?php

namespace Tests\Feature\Accounting;

use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Exceptions\InvalidJournalLineException;
use Softmit\DoubleEntry\Exceptions\JournalStateException;
use Softmit\DoubleEntry\Exceptions\UnbalancedJournalException;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\JournalLineDraft;
use Softmit\DoubleEntry\Support\Money;

/**
 * Core posting rules: a journal is either balanced, atomic, idempotent and
 * traceable — or it does not exist.
 */
class JournalPostingTest extends AccountingTestCase
{
    protected function poster(): JournalPoster
    {
        return app(JournalPoster::class);
    }

    protected function saleDraft(string $amount = '1000.00'): JournalDraft
    {
        return JournalDraft::make('2026-11-02')
            ->from(SourceType::SALE, 101, 'INV-101')
            ->party(PartyType::CUSTOMER, 7)
            ->about('Cash sale')
            ->key('sale:101')
            ->debit(AccountRole::CASH, $amount)
            ->credit(AccountRole::SALES_REVENUE, $amount);
    }

    public function test_a_balanced_journal_posts_with_totals_status_and_actor(): void
    {
        $journal = $this->poster()->post($this->saleDraft());

        $this->assertTrue($journal->exists);
        $this->assertSame(JournalStatus::POSTED, $journal->status);
        $this->assertEquals('1000.00', $journal->total_debit);
        $this->assertEquals('1000.00', $journal->total_credit);
        $this->assertNotNull($journal->posted_at);
        $this->assertCount(2, $journal->lines);
        $this->assertTrue($journal->isBalanced());
    }

    public function test_debits_and_credits_land_on_the_configured_accounts_not_on_codes(): void
    {
        $journal = $this->poster()->post($this->saleDraft());

        $cash = $this->account(AccountRole::CASH);
        $revenue = $this->account(AccountRole::SALES_REVENUE);

        $debitLine = $journal->lines->firstWhere('account_id', $cash->id);
        $creditLine = $journal->lines->firstWhere('account_id', $revenue->id);

        $this->assertEquals('1000.00', $debitLine->debit);
        $this->assertEquals('0.00', $debitLine->credit);
        $this->assertEquals('1000.00', $creditLine->credit);
    }

    public function test_an_unbalanced_journal_is_not_posted_and_leaves_no_rows(): void
    {
        $before = [JournalEntry::count(), DB::table('accounting_journal_lines')->count()];

        $draft = JournalDraft::make('2026-11-02')
            ->debit(AccountRole::CASH, '500.00')
            ->credit(AccountRole::SALES_REVENUE, '499.99');

        try {
            $this->poster()->post($draft);
            $this->fail('Expected UnbalancedJournalException.');
        } catch (UnbalancedJournalException $e) {
            $this->assertEquals('500.00', $e->totalDebit);
            $this->assertEquals('499.99', $e->totalCredit);
        }

        $this->assertSame($before[0], JournalEntry::count());
        $this->assertSame($before[1], DB::table('accounting_journal_lines')->count());
    }

    public function test_a_failed_post_consumes_neither_a_journal_number_nor_a_row(): void
    {
        $first = $this->poster()->post($this->saleDraft());
        $sequenceBefore = (int) DB::table('accounting_sequences')->where('name', 'JV-2026')->value('next_value');

        try {
            $this->poster()->post(
                JournalDraft::make('2026-11-02')
                    ->key('sale:999')
                    ->debit(AccountRole::CASH, '10.00')
            );
            $this->fail('Expected UnbalancedJournalException.');
        } catch (UnbalancedJournalException $e) {
            // expected
        }

        $this->assertSame($sequenceBefore, (int) DB::table('accounting_sequences')->where('name', 'JV-2026')->value('next_value'));
        $this->assertSame(1, JournalEntry::count());
        $this->assertSame('JV-2026-000001', $first->journal_no);
    }

    public function test_posting_the_same_business_key_twice_returns_the_original_journal(): void
    {
        $first = $this->poster()->post($this->saleDraft());
        $second = $this->poster()->post($this->saleDraft('2000.00'));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, JournalEntry::count());
        $this->assertEquals('1000.00', $second->total_debit, 'The replayed amount must not overwrite the posted journal.');
    }

    public function test_journal_numbers_are_unique_sequential_and_year_scoped(): void
    {
        $years = ['2026-11-02', '2026-11-03', '2027-01-05'];
        $numbers = [];

        foreach ($years as $index => $date) {
            $numbers[] = $this->poster()->post(
                $this->saleDraft()->key('sale:' . (200 + $index))->dated($date)
            )->journal_no;
        }

        $this->assertSame('JV-2026-000001', $numbers[0]);
        $this->assertSame('JV-2026-000002', $numbers[1]);
        $this->assertSame('JV-2027-000001', $numbers[2], 'Numbering restarts per year.');
        $this->assertSame(3, JournalEntry::count());
        $this->assertSame(3, count(array_unique($numbers)));
    }

    public function test_a_journal_needs_at_least_one_line(): void
    {
        $this->expectException(JournalStateException::class);

        $this->poster()->post(JournalDraft::make()->from(SourceType::MANUAL));
    }

    public function test_a_line_cannot_carry_both_sides_or_no_side(): void
    {
        $both = JournalDraft::make('2026-11-02')
            ->line(new JournalLineDraft(AccountRole::CASH, '10.00', '10.00'));

        try {
            $this->poster()->post($both);
            $this->fail('Expected InvalidJournalLineException for a line with both sides.');
        } catch (InvalidJournalLineException $e) {
            $this->assertStringContainsString('both a debit and a credit', $e->getMessage());
        }

        $zero = JournalDraft::make('2026-11-02')
            ->line(new JournalLineDraft(AccountRole::CASH, '0.00', '0.00'))
            ->line(new JournalLineDraft(AccountRole::CASH, '0.00', '0.00'));

        $this->expectException(InvalidJournalLineException::class);
        $this->poster()->post($zero);
    }

    public function test_negative_amounts_are_rejected_rather_than_silently_flipped(): void
    {
        $this->expectException(InvalidJournalLineException::class);

        $this->poster()->post(
            JournalDraft::make('2026-11-02')
                ->debit(AccountRole::CASH, '-100.00')
                ->credit(AccountRole::SALES_REVENUE, '100.00')
        );
    }

    public function test_source_party_and_actor_are_three_separate_traces(): void
    {
        $journal = $this->poster()->post(
            $this->saleDraft()->actor(42)
        );

        $this->assertSame(SourceType::SALE, $journal->source_type);
        $this->assertSame(101, (int) $journal->source_id);
        $this->assertSame('INV-101', $journal->reference);

        $this->assertSame(PartyType::CUSTOMER, $journal->party_type);
        $this->assertSame(7, (int) $journal->party_id);

        $this->assertSame(42, (int) $journal->created_by);
        $this->assertSame(42, (int) $journal->posted_by);

        // The source label reads as a business record, never as a class name.
        $this->assertSame('Sale #101', $journal->sourceLabel());
        $this->assertStringNotContainsString('App\\Models', (string) $journal->source_type->value);
    }

    public function test_lines_without_their_own_party_inherit_the_journal_party(): void
    {
        $journal = $this->poster()->post($this->saleDraft());

        foreach ($journal->lines as $line) {
            $this->assertSame(PartyType::CUSTOMER, $line->party_type);
            $this->assertSame(7, (int) $line->party_id);
        }
    }

    public function test_business_events_before_the_cutover_are_refused(): void
    {
        config(['double-entry.cutover_date' => '2026-10-01']);

        $this->expectException(JournalStateException::class);

        $this->poster()->post(
            $this->saleDraft()->key('sale:999')->dated('2026-09-15')
        );
    }

    public function test_opening_and_manual_journals_may_predate_the_cutover(): void
    {
        config(['double-entry.cutover_date' => '2026-10-01']);

        $opening = $this->poster()->post(
            JournalDraft::make('2026-09-30')
                ->from(SourceType::OPENING, null, 'Opening balances')
                ->debit(AccountRole::CASH, '50000.00')
                ->credit(AccountRole::OWNER_CAPITAL, '50000.00')
        );

        $this->assertTrue($opening->isPosted());
        $this->assertSame(1, JournalEntry::where('source_type', SourceType::OPENING->value)->count());
    }

    public function test_amounts_are_stored_as_exact_decimal_strings(): void
    {
        $journal = $this->poster()->post(
            JournalDraft::make('2026-11-02')
                ->key('sale:float')
                ->debit(AccountRole::CASH, 0.1 + 0.2)
                ->credit(AccountRole::SALES_REVENUE, '0.30')
        );

        $this->assertSame('0.30', (string) $journal->total_debit);
        $this->assertSame('0.30', (string) $journal->total_credit);
        $this->assertTrue(Money::equals($journal->total_debit, $journal->total_credit));
    }

    public function test_a_draft_is_invisible_to_reports_and_can_be_edited_then_posted(): void
    {
        $draft = $this->poster()->save(
            JournalDraft::make('2026-11-02')
                ->from(SourceType::SALE, 500, 'INV-500')
                ->debit(AccountRole::CASH, '100.00')
        );

        $this->assertTrue($draft->isDraft());
        $this->assertSame(0, JournalEntry::query()->posted()->count());
        $this->assertTrue($draft->isEditable());

        $draft->lines()->create([
            'account_id' => $this->accountId(AccountRole::SALES_REVENUE),
            'credit'     => '100.00',
        ]);

        $posted = $this->poster()->postDraft($draft->fresh());

        $this->assertTrue($posted->isPosted());
        $this->assertEquals('100.00', $posted->total_debit);
        $this->assertSame(1, JournalEntry::query()->posted()->count());
    }

    public function test_an_incomplete_draft_cannot_be_posted(): void
    {
        $draft = $this->poster()->save(
            JournalDraft::make('2026-11-02')->debit(AccountRole::CASH, '100.00')
        );

        $this->expectException(UnbalancedJournalException::class);
        $this->poster()->postDraft($draft);
    }

    public function test_posting_a_journal_twice_is_refused(): void
    {
        $journal = $this->poster()->post($this->saleDraft());

        $this->expectException(JournalStateException::class);
        $this->poster()->postDraft($journal);
    }
}
