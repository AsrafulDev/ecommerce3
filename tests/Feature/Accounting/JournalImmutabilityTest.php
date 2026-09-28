<?php

namespace Tests\Feature\Accounting;

use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Exceptions\PostedJournalImmutableException;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Services\ReversalService;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\Money;

/**
 * Posted history is immutable; corrections are additive.
 *
 * These guards live on the model so a controller, a seeder, a queue job or a
 * tinker session all hit the same wall.
 */
class JournalImmutabilityTest extends AccountingTestCase
{
    protected function poster(): JournalPoster
    {
        return app(JournalPoster::class);
    }

    protected function reverser(): ReversalService
    {
        return app(ReversalService::class);
    }

    protected function postedSale(string $amount = '1000.00', int $orderId = 101): JournalEntry
    {
        return $this->poster()->post(
            JournalDraft::make('2026-11-02')
                ->key("sale:{$orderId}")
                ->from(\Softmit\DoubleEntry\Enums\SourceType::SALE, $orderId)
                ->debit(AccountRole::CASH, $amount)
                ->credit(AccountRole::SALES_REVENUE, $amount)
                ->actor(5)
        );
    }

    public function test_a_posted_journal_cannot_have_its_amount_changed(): void
    {
        $journal = $this->postedSale();

        $this->expectException(PostedJournalImmutableException::class);
        $journal->update(['total_debit' => '1.00']);
    }

    public function test_a_posted_journal_cannot_have_its_party_changed(): void
    {
        $journal = $this->postedSale();

        $this->expectException(PostedJournalImmutableException::class);
        $journal->update(['party_id' => 999]);
    }

    public function test_a_posted_journal_cannot_have_its_description_edited(): void
    {
        $journal = $this->postedSale();

        $this->expectException(PostedJournalImmutableException::class);
        $journal->update(['description' => 'rewritten']);
    }

    public function test_a_posted_journal_cannot_be_deleted(): void
    {
        $journal = $this->postedSale();

        $this->expectException(PostedJournalImmutableException::class);
        $journal->delete();
    }

    public function test_a_reversed_journal_is_still_protected(): void
    {
        $journal = $this->postedSale();
        $this->reverser()->reverse($journal, 'wrong amount');

        $this->expectException(PostedJournalImmutableException::class);
        $journal->refresh()->delete();
    }

    public function test_posted_lines_cannot_be_edited_or_removed(): void
    {
        $journal = $this->postedSale();
        $line = $journal->lines()->first();

        try {
            $line->update(['debit' => '7.00']);
            $this->fail('Expected PostedJournalImmutableException for a line edit.');
        } catch (PostedJournalImmutableException $e) {
            $this->assertStringContainsString('cannot be edit', $e->getMessage());
        }

        $this->expectException(PostedJournalImmutableException::class);
        $line->delete();
    }

    public function test_a_draft_and_its_lines_can_be_edited_and_deleted(): void
    {
        $draft = $this->poster()->save(
            JournalDraft::make('2026-11-02')->debit(AccountRole::CASH, '50.00')
        );

        $draft->lines()->create([
            'account_id' => $this->accountId(AccountRole::SALES_REVENUE),
            'credit'     => '40.00',
        ]);

        $line = $draft->lines()->first();
        $line->update(['credit' => '50.00']);
        $this->assertEquals('50.00', $line->fresh()->credit);

        $line->delete();
        $draft->delete();

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_reversal_posts_the_mirror_image_and_marks_the_original_reversed(): void
    {
        $journal = $this->postedSale();

        $reversal = $this->reverser()->reverse($journal, 'Charged the wrong customer', 9);

        $this->assertTrue($reversal->isPosted());
        $this->assertSame(JournalStatus::REVERSED, $journal->refresh()->status);
        $this->assertSame(9, (int) $journal->reversed_by);
        $this->assertSame('Charged the wrong customer', $journal->reversal_reason);
        $this->assertSame($journal->id, (int) $reversal->reversal_of_id);
        $this->assertSame("Reversal of {$journal->journal_no}", $reversal->reference);

        // Every line is flipped to the opposite side, at the same amount.
        $original = $journal->lines()->get()->keyBy('account_id');
        foreach ($reversal->lines as $line) {
            $before = $original[$line->account_id];

            $this->assertEquals($before->debit, $line->credit);
            $this->assertEquals($before->credit, $line->debit);
        }
    }

    public function test_a_reversal_nets_the_original_out_of_every_account(): void
    {
        $journal = $this->postedSale('1000.00');
        $this->assertSame('1000.00', $this->account(AccountRole::CASH)->balance());

        $this->reverser()->reverse($journal, 'error');

        // Both journals stay in the books and offset exactly: history is visible,
        // the balance is as if the mistake never happened.
        $this->assertSame('0.00', $this->account(AccountRole::CASH)->balance());
        $this->assertSame('0.00', $this->account(AccountRole::SALES_REVENUE)->balance());
        $this->assertSame(2, JournalEntry::count());
    }

    public function test_a_journal_can_only_be_reversed_once(): void
    {
        $journal = $this->postedSale();
        $this->reverser()->reverse($journal, 'first');

        $this->expectException(\Softmit\DoubleEntry\Exceptions\JournalStateException::class);
        $this->reverser()->reverse($journal->refresh(), 'second');
    }

    public function test_a_draft_is_not_reversed_but_deleted(): void
    {
        $draft = $this->poster()->save(
            JournalDraft::make('2026-11-02')->debit(AccountRole::CASH, '50.00')
        );

        $this->expectException(\Softmit\DoubleEntry\Exceptions\JournalStateException::class);
        $this->reverser()->reverse($draft, 'oops');
    }

    public function test_reversal_keeps_the_same_party_trace(): void
    {
        $journal = $this->poster()->post(
            JournalDraft::make('2026-11-02')
                ->key('sale:777')
                ->party(\Softmit\DoubleEntry\Enums\PartyType::CUSTOMER, 21)
                ->debit(AccountRole::CASH, '300.00')
                ->credit(AccountRole::SALES_REVENUE, '300.00')
        );

        $reversal = $this->reverser()->reverse($journal, 'wrong party');

        $this->assertSame(\Softmit\DoubleEntry\Enums\PartyType::CUSTOMER, $reversal->party_type);
        $this->assertSame(21, (int) $reversal->party_id);
        $this->assertSame(21, (int) $reversal->lines->first()->party_id);
    }

    public function test_balances_are_derived_from_lines_and_express_normal_balance(): void
    {
        $this->postedSale('1000.00');

        // Cash is a debit-normal asset: 1000 in => +1000.
        $this->assertSame('1000.00', $this->account(AccountRole::CASH)->balance());

        // Revenue is credit-normal: 1000 credited => +1000, not -1000.
        $this->assertSame('1000.00', $this->account(AccountRole::SALES_REVENUE)->balance());

        // A contra account (debit-normal, revenue-class) reads as a reduction.
        $this->poster()->post(
            JournalDraft::make('2026-11-03')
                ->key('sale:return:5')
                ->debit(AccountRole::SALES_RETURNS, '200.00')
                ->credit(AccountRole::CASH, '200.00')
        );

        $this->assertSame('200.00', $this->account(AccountRole::SALES_RETURNS)->balance());
        $this->assertSame('800.00', $this->account(AccountRole::CASH)->balance());
    }

    public function test_drafts_are_excluded_from_balances(): void
    {
        $this->postedSale('1000.00');
        $this->poster()->save(
            JournalDraft::make('2026-11-04')->debit(AccountRole::CASH, '9000.00')
        );

        $this->assertSame('1000.00', $this->account(AccountRole::CASH)->balance());
    }

    public function test_as_at_date_balances_ignore_later_journals(): void
    {
        $this->poster()->post(
            JournalDraft::make('2026-11-02')
                ->key('sale:601')
                ->debit(AccountRole::CASH, '100.00')
                ->credit(AccountRole::SALES_REVENUE, '100.00')
        );
        $this->poster()->post(
            JournalDraft::make('2026-12-01')
                ->key('sale:602')
                ->debit(AccountRole::CASH, '50.00')
                ->credit(AccountRole::SALES_REVENUE, '50.00')
        );

        $this->assertSame('100.00', $this->account(AccountRole::CASH)->balance('2026-11-30'));
        $this->assertSame('150.00', $this->account(AccountRole::CASH)->balance('2026-12-31'));
    }

    public function test_trial_balance_of_the_whole_ledger_always_zeroes_out(): void
    {
        $this->postedSale('1000.00', 801);
        $this->postedSale('250.50', 802);
        $this->reverser()->reverse($this->postedSale('99.99', 803), 'reversed');

        $rows = JournalEntry::query()->posted()
            ->with('lines')
            ->get();

        $debit = Money::sum($rows->flatMap(fn ($j) => $j->lines->pluck('debit'))->all());
        $credit = Money::sum($rows->flatMap(fn ($j) => $j->lines->pluck('credit'))->all());

        $this->assertTrue(Money::equals($debit, $credit), "Trial balance drifted: {$debit} vs {$credit}");
    }
}
