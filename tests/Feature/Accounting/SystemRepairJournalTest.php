<?php

namespace Tests\Feature\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Services\Audit\SystemCheck;
use App\Services\Audit\SystemRepair;
use Softmit\DoubleEntry\Models\JournalEntry;

/**
 * The repair posts a missing expense journal once, and a second repair posts nothing more.
 */
class SystemRepairJournalTest extends LiveBooksTestCase
{
    public function test_a_missing_expense_journal_is_posted_once_by_the_repair(): void
    {
        $this->openBooks();
        // Created the way the expense screen does it: the expense and its cash row together.
        $expense = Expense::create([
            'title' => 'Electricity', 'amount' => 75, 'expense_date' => now()->toDateString(),
            'category' => 'utility', 'note' => 'Bill', 'created_by' => 1,
        ]);
        $fund = FundTransaction::create([
            'direction' => 'out', 'source' => 'expense', 'source_id' => $expense->id,
            'amount' => 75, 'note' => 'Electricity', 'created_by' => 1,
        ]);
        $expense->update(['fund_transaction_id' => $fund->id]);

        $before = (int) collect((new SystemCheck())->run())->firstWhere('key', 'journals')['count'];
        $this->assertSame(1, $before, 'the check reports the expense without a journal');

        app(SystemRepair::class)->run();
        $this->assertSame(1, JournalEntry::where('source_type', 'expense')->count());

        app(SystemRepair::class)->run();
        $this->assertSame(1, JournalEntry::where('source_type', 'expense')->count(), 'a second repair must not post again');
        $this->checkControls('after system repair');
    }

    public function test_an_expense_with_no_cash_row_is_reported_and_never_journaled(): void
    {
        $this->openBooks();
        Expense::create([
            'title' => 'Unpaid fee', 'amount' => 40, 'expense_date' => now()->toDateString(),
            'category' => 'general', 'note' => 'No cash', 'created_by' => 1,
        ]);

        app(SystemRepair::class)->run();

        $this->assertSame(0, JournalEntry::where('source_type', 'expense')->count(), 'no cash row means no cash journal');
        $withoutCash = collect((new SystemCheck())->run())->firstWhere('key', 'expenses_without_cash');
        $this->assertSame(1, $withoutCash['count']);
        $this->assertFalse($withoutCash['repairable']);
    }
}
