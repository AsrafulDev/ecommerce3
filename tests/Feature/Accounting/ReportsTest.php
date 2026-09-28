<?php

namespace Tests\Feature\Accounting;

use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Services\LedgerService;
use Softmit\DoubleEntry\Services\ProfitAndLossReport;
use Softmit\DoubleEntry\Services\ReversalService;
use Softmit\DoubleEntry\Services\TrialBalanceReport;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\Money;

/**
 * Reports must agree with each other and with the ledger.
 *
 * The application currently has several hand-rolled profit/COGS queries that
 * disagree; these tests are the single definition the integration stage has to
 * satisfy.
 */
class ReportsTest extends AccountingTestCase
{
    protected function poster(): JournalPoster
    {
        return app(JournalPoster::class);
    }

    protected function postEntry(string $date, string $key, array $lines, ?PartyType $party = null, int|string|null $partyId = null): void
    {
        $draft = JournalDraft::make($date)
            ->from(SourceType::MANUAL, null, $key)
            ->key($key);

        if ($party && $partyId) {
            $draft->party($party, $partyId);
        }

        foreach ($lines as [$role, $side, $amount]) {
            $side === 'debit' ? $draft->debit($role, $amount) : $draft->credit($role, $amount);
        }

        $this->poster()->post($draft);
    }

    public function test_trial_balance_debits_and_credits_agree_for_the_period_and_at_closing(): void
    {
        $this->postEntry('2026-11-01', 'opening:1', [[AccountRole::CASH, 'debit', '50000.00'], [AccountRole::OWNER_CAPITAL, 'credit', '50000.00']]);
        $this->postEntry('2026-11-05', 'sale:1', [[AccountRole::CASH, 'debit', '1200.00'], [AccountRole::SALES_REVENUE, 'credit', '1200.00']]);
        $this->postEntry('2026-11-06', 'expense:1', [[AccountRole::RENT_EXPENSE, 'debit', '300.00'], [AccountRole::CASH, 'credit', '300.00']]);

        $report = app(TrialBalanceReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertTrue($report['balanced'], 'Difference: ' . $report['difference'] . ' / ' . $report['closing_difference']);
        $this->assertSame('51500.00', $report['totals']['movement_debit']);
        $this->assertSame('51500.00', $report['totals']['movement_credit']);

        $byCode = collect($report['rows'])->keyBy('code');

        $this->assertSame('50900.00', $byCode['1000']['closing']);   // 50000 + 1200 - 300
        $this->assertSame('1200.00', $byCode['4000']['closing']);
        $this->assertSame('300.00', $byCode['6100']['closing']);
        $this->assertSame('50000.00', $byCode['3000']['closing']);
    }

    public function test_opening_balances_are_brought_forward_and_a_shorter_period_only_shows_movement(): void
    {
        $this->postEntry('2026-11-01', 'opening:2', [[AccountRole::CASH, 'debit', '1000.00'], [AccountRole::OWNER_CAPITAL, 'credit', '1000.00']]);
        $this->postEntry('2026-12-03', 'sale:2', [[AccountRole::CASH, 'debit', '250.00'], [AccountRole::SALES_REVENUE, 'credit', '250.00']]);

        $december = app(TrialBalanceReport::class)->build('2026-12-01', '2026-12-31');
        $cash = collect($december['rows'])->firstWhere('code', '1000');

        $this->assertSame('1000.00', $cash['opening'], 'November must be carried forward, not re-counted.');
        $this->assertSame('250.00', $cash['debit'], 'December shows only December movement.');
        $this->assertSame('1250.00', $cash['closing']);
        $this->assertTrue($december['balanced']);
    }

    public function test_drafts_are_absent_from_every_report(): void
    {
        $this->postEntry('2026-11-05', 'sale:3', [[AccountRole::CASH, 'debit', '100.00'], [AccountRole::SALES_REVENUE, 'credit', '100.00']]);

        app(JournalPoster::class)->save(
            JournalDraft::make('2026-11-06')->debit(AccountRole::RENT_EXPENSE, '9999.00')
        );

        $pl = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');
        $this->assertSame('0.00', $pl['groups']['expense']['total']);
        $this->assertSame('100.00', $pl['net_profit']);

        $tb = app(TrialBalanceReport::class)->build('2026-11-01', '2026-11-30');
        $this->assertNull(collect($tb['rows'])->firstWhere('code', '6100'));
    }

    public function test_profit_and_loss_separates_revenue_cogs_and_expenses(): void
    {
        $this->postEntry('2026-11-05', 'sale:4', [[AccountRole::ACCOUNTS_RECEIVABLE, 'debit', '5000.00'], [AccountRole::SALES_REVENUE, 'credit', '5000.00']]);
        $this->postEntry('2026-11-05', 'cogs:4', [[AccountRole::COGS, 'debit', '3200.00'], [AccountRole::INVENTORY, 'credit', '3200.00']]);
        $this->postEntry('2026-11-07', 'expense:4', [[AccountRole::SALARY_EXPENSE, 'debit', '500.00'], [AccountRole::CASH, 'credit', '500.00']]);
        $this->postEntry('2026-11-08', 'income:4', [[AccountRole::CASH, 'debit', '150.00'], [AccountRole::DELIVERY_INCOME, 'credit', '150.00']]);

        $pl = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertSame('5150.00', $pl['revenue']);
        $this->assertSame('3200.00', $pl['cost_of_sales']);
        $this->assertSame('1950.00', $pl['gross_profit']);
        $this->assertSame('500.00', $pl['expenses']);
        $this->assertSame('1450.00', $pl['net_profit']);
    }

    public function test_sales_returns_reduce_revenue_instead_of_becoming_an_expense(): void
    {
        $this->postEntry('2026-11-05', 'sale:5', [[AccountRole::CASH, 'debit', '1000.00'], [AccountRole::SALES_REVENUE, 'credit', '1000.00']]);
        $this->postEntry('2026-11-06', 'return:5', [[AccountRole::SALES_RETURNS, 'debit', '250.00'], [AccountRole::CASH, 'credit', '250.00']]);

        $pl = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertSame('750.00', $pl['revenue'], 'Contra revenue nets off the revenue subtotal.');
        $this->assertSame('-250.00', collect($pl['groups']['revenue']['rows'])->firstWhere('code', '4900')['amount']);
        $this->assertSame('0.00', $pl['expenses']);
        $this->assertSame('750.00', $pl['net_profit']);
    }

    public function test_owner_capital_and_drawings_can_never_reach_profit(): void
    {
        $this->postEntry('2026-11-01', 'capital:6', [[AccountRole::CASH, 'debit', '1000000.00'], [AccountRole::OWNER_CAPITAL, 'credit', '1000000.00']]);
        $this->postEntry('2026-11-02', 'draw:6', [[AccountRole::OWNER_DRAWINGS, 'debit', '900000.00'], [AccountRole::CASH, 'credit', '900000.00']]);

        $pl = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertSame('0.00', $pl['revenue']);
        $this->assertSame('0.00', $pl['net_profit']);

        // But the cash actually moved, and the balance sheet reflects it.
        $this->assertSame('100000.00', $this->account(AccountRole::CASH)->balance());

        $tb = app(TrialBalanceReport::class)->build('2026-11-01', '2026-11-30');
        $this->assertTrue($tb['balanced']);
    }

    public function test_a_reversed_journal_leaves_profit_exactly_where_it_started(): void
    {
        $journal = $this->poster()->post(
            JournalDraft::make('2026-11-05')
                ->key('sale:7')
                ->debit(AccountRole::CASH, '1000.00')
                ->credit(AccountRole::SALES_REVENUE, '1000.00')
        );

        $before = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');

        // Dated inside the period on purpose: a reversal posted today would land in
        // today's books and leave November's profit untouched, which is correct.
        app(ReversalService::class)->reverse($journal, 'wrong order', null, '2026-11-20');

        $after = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertSame('1000.00', $before['net_profit']);
        $this->assertSame('0.00', $after['net_profit']);
        $this->assertSame([], array_values(array_filter(
            $after['groups']['revenue']['rows'],
            fn ($row) => $row['code'] === '4000'
        )), 'The account drops out entirely rather than showing a zero row.');
    }

    public function test_period_boundaries_are_inclusive_on_both_ends(): void
    {
        $this->postEntry('2026-11-01', 'sale:8', [[AccountRole::CASH, 'debit', '10.00'], [AccountRole::SALES_REVENUE, 'credit', '10.00']]);
        $this->postEntry('2026-11-30', 'sale:9', [[AccountRole::CASH, 'debit', '20.00'], [AccountRole::SALES_REVENUE, 'credit', '20.00']]);
        $this->postEntry('2026-12-01', 'sale:10', [[AccountRole::CASH, 'debit', '40.00'], [AccountRole::SALES_REVENUE, 'credit', '40.00']]);

        $november = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertSame('30.00', $november['net_profit'], 'First and last day of the period must both count.');
    }

    public function test_account_ledger_carries_an_opening_balance_and_a_running_total(): void
    {
        $this->postEntry('2026-10-15', 'opening:11', [[AccountRole::CASH, 'debit', '500.00'], [AccountRole::OWNER_CAPITAL, 'credit', '500.00']]);
        $this->postEntry('2026-11-05', 'in:11', [[AccountRole::CASH, 'debit', '200.00'], [AccountRole::SALES_REVENUE, 'credit', '200.00']]);
        $this->postEntry('2026-11-06', 'out:11', [[AccountRole::CASH, 'credit', '50.00'], [AccountRole::RENT_EXPENSE, 'debit', '50.00']]);

        $ledger = app(LedgerService::class)->forAccount(AccountRole::CASH, '2026-11-01', '2026-11-30');

        $this->assertSame('500.00', $ledger['opening']);
        $this->assertSame('650.00', $ledger['closing']);
        $this->assertCount(2, $ledger['rows']);

        $balances = collect($ledger['rows']->items())->pluck('balance')->all();
        $this->assertSame(['700.00', '650.00'], $balances);

        $first = $ledger['rows'][0];
        $this->assertSame('200.00', $first['debit']);
        $this->assertSame('0.00', $first['credit']);
        $this->assertNotEmpty($first['journal_no']);
    }

    public function test_running_balance_survives_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postEntry('2026-11-0' . $i, "in:12:{$i}", [
                [AccountRole::CASH, 'debit', '100.00'],
                [AccountRole::SALES_REVENUE, 'credit', '100.00'],
            ]);
        }

        $ledger = app(LedgerService::class);
        $page1 = $ledger->forAccount(AccountRole::CASH, '2026-11-01', '2026-11-30', 2, 1);
        $page2 = $ledger->forAccount(AccountRole::CASH, '2026-11-01', '2026-11-30', 2, 2);

        $this->assertSame('200.00', $page1['rows'][1]['balance']);
        $this->assertSame('300.00', $page2['rows'][0]['balance'], 'Page 2 must continue the running balance, not restart it.');
        $this->assertSame('500.00', $ledger->forAccount(AccountRole::CASH, '2026-11-01', '2026-11-30', 10, 1)['closing']);
    }

    public function test_party_statement_shows_only_the_subledger_not_the_cash_leg(): void
    {
        // Credit sale: receivable carries the customer; then the customer pays.
        $this->poster()->post(
            JournalDraft::make('2026-11-05')
                ->key('sale:13')
                ->from(SourceType::SALE, 13)
                ->party(PartyType::CUSTOMER, 55)
                ->debit(AccountRole::ACCOUNTS_RECEIVABLE, '900.00')
                ->credit(AccountRole::SALES_REVENUE, '900.00')
        );
        $this->poster()->post(
            JournalDraft::make('2026-11-09')
                ->key('customer_payment:13')
                ->from(SourceType::CUSTOMER_PAYMENT, 13)
                ->party(PartyType::CUSTOMER, 55)
                ->debit(AccountRole::CASH, '900.00')
                ->credit(AccountRole::ACCOUNTS_RECEIVABLE, '900.00')
        );

        $statement = app(LedgerService::class)->forParty(PartyType::CUSTOMER, 55, '2026-11-01', '2026-11-30');

        $this->assertSame($this->accountId(AccountRole::ACCOUNTS_RECEIVABLE), $statement['account']->id);
        $this->assertCount(2, $statement['rows'], 'Both receivable legs appear; the cash leg does not.');
        $this->assertSame('900.00', $statement['rows'][0]['balance']);
        $this->assertSame('0.00', $statement['closing'], 'The customer owes nothing once paid.');

        // The cash line still carries the party as a trace, and is visible in the
        // cash ledger rather than the customer statement.
        $this->assertSame('900.00', $this->account(AccountRole::CASH)->balance());
    }

    public function test_cash_positions_report_only_money_actually_received(): void
    {
        $this->poster()->post(
            JournalDraft::make('2026-11-05')
                ->key('sale:14')
                ->debit(AccountRole::CASH, '400.00')
                ->credit(AccountRole::SALES_REVENUE, '400.00')
        );
        $this->poster()->post(
            JournalDraft::make('2026-11-06')
                ->key('sale:15')
                ->debit(AccountRole::ACCOUNTS_RECEIVABLE, '600.00')
                ->credit(AccountRole::SALES_REVENUE, '600.00')
        );

        $positions = app(LedgerService::class)->cashPositions('2026-11-01', '2026-11-30');

        $this->assertArrayHasKey('default', $positions);
        $this->assertSame('400.00', $positions['default']['closing'], 'Uncollected receivables are not cash.');
        $this->assertSame('400.00', $positions['default']['in']);
        $this->assertSame('0.00', $positions['default']['out']);
        $this->assertSame('400.00', app(LedgerService::class)->totalCash());
    }

    public function test_reports_reconcile_with_each_other(): void
    {
        $this->postEntry('2026-11-01', 'cap:16', [[AccountRole::CASH, 'debit', '10000.00'], [AccountRole::OWNER_CAPITAL, 'credit', '10000.00']]);
        $this->postEntry('2026-11-05', 'sale:16', [[AccountRole::CASH, 'debit', '3000.00'], [AccountRole::SALES_REVENUE, 'credit', '3000.00']]);
        $this->postEntry('2026-11-06', 'cogs:16', [[AccountRole::COGS, 'debit', '1800.00'], [AccountRole::INVENTORY, 'credit', '1800.00']]);
        $this->postEntry('2026-11-07', 'exp:16', [[AccountRole::RENT_EXPENSE, 'debit', '700.00'], [AccountRole::CASH, 'credit', '700.00']]);

        $pl = app(ProfitAndLossReport::class)->build('2026-11-01', '2026-11-30');
        $tb = app(TrialBalanceReport::class)->build('2026-11-01', '2026-11-30');

        $this->assertSame('500.00', $pl['net_profit']);

        // The profit-and-loss rows of the trial balance must add up to the same
        // net profit the P&L reports — one number, two presentations.
        $profitFromLedger = collect($tb['rows'])
            ->filter(fn ($row) => in_array($row['account_type'], ['Revenue', 'Cost of Sales', 'Expense'], true))
            ->reduce(function ($carry, $row) {
                $creditNormal = $row['account_type'] === 'Revenue';
                $contribution = $creditNormal
                    ? Money::subtract($row['credit'], $row['debit'])
                    : Money::subtract($row['debit'], $row['credit']);

                // Costs and expenses reduce profit; revenue increases it.
                return $creditNormal ? Money::add($carry, $contribution) : Money::subtract($carry, $contribution);
            }, Money::zero());

        $this->assertSame($pl['net_profit'], $profitFromLedger);
    }
}
