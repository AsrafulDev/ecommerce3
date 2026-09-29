<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Support\Accounting\ManualPostingResult;
use Carbon\Carbon;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Models\FundAccount;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\PostingFailureLogger;
use Softmit\DoubleEntry\Services\ReversalService;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\Money;

/**
 * The books side of the manually operated money screens: expense, money-in and
 * owner withdrawal.
 *
 * Phase 1 keeps the legacy fund/expense rows exactly as they are — this class
 * adds the double entry next to them, never instead of them. Two rules make that
 * safe to run twice by accident:
 *
 *  1. every journal carries a posting key derived from the source row, so a
 *     double submit or a replayed request cannot create a second journal;
 *  2. once a source row's journal is POSTED the row stops being editable here,
 *     because the ledger cannot hold a different figure from its own journals.
 *     Corrections go through a reversal, which is what leaves an audit trail.
 *
 * Posting never blocks the money record: a failure is written to the posting
 * failure table and reported back as a warning, so the operator sees the books
 * disagree with reality instead of being quietly misled.
 */
class ManualEntryService
{
    public function __construct(
        protected PostingFailureLogger $failures,
        protected ReversalService $reversals,
    ) {
    }

    /**
     * Dr expense account / Cr the cash account the money left.
     */
    public function expense(Expense $expense): ManualPostingResult
    {
        $amount = Money::of($expense->amount);

        // expense_date is a bare date column on this model, not a Carbon, so it
        // has to be normalised before it can be compared or posted.
        $date = Carbon::parse($expense->expense_date ?? now())->format('Y-m-d');

        if ($this->refuses($date)) {
            return ManualPostingResult::preCutover();
        }

        return $this->post(
            JournalDraft::make($date)
                ->from(SourceType::EXPENSE, $expense->id, $expense->title)
                ->key($this->nextKey(SourceType::EXPENSE, $expense->id))
                ->about("Expense: {$expense->title}" . ($expense->note ? " — {$expense->note}" : ''))
                ->actor($expense->created_by)
                ->meta(['legacy' => 'expenses', 'category' => $expense->category])
                ->debit($this->roleForCategory($expense->category), $amount, $expense->title)
                ->credit($this->cashAccount(), $amount, 'Paid out')
        );
    }

    /**
     * Money into the till. Either the owner putting their own money in (equity,
     * never profit) or genuine income.
     */
    public function moneyIn(FundTransaction $tx, string $nature): ManualPostingResult
    {
        $date = $tx->created_at->format('Y-m-d');

        if ($this->refuses($date)) {
            return ManualPostingResult::preCutover();
        }

        $role = $nature === 'owner_capital'
            ? config('double-entry.manual.capital_role', AccountRole::OWNER_CAPITAL)
            : config('double-entry.manual.income_role', AccountRole::OTHER_INCOME);

        $sourceType = $nature === 'owner_capital' ? SourceType::OWNER_CAPITAL : SourceType::INCOME;

        $amount = Money::of($tx->amount);

        return $this->post(
            JournalDraft::make($date)
                ->from($sourceType, $tx->id, $tx->note)
                ->key($this->nextKey($sourceType, $tx->id))
                ->about($tx->note ?: ($nature === 'owner_capital' ? 'Owner capital introduced' : 'Other income received'))
                ->actor($tx->created_by)
                ->meta(['legacy' => 'fund_transactions', 'nature' => $nature])
                ->debit($this->cashAccount(), $amount, 'Received')
                ->credit($role, $amount)
        );
    }

    /**
     * Dr owner drawings / Cr cash. Taking money out is not an expense.
     */
    public function withdrawal(FundTransaction $tx): ManualPostingResult
    {
        $date = $tx->created_at->format('Y-m-d');

        if ($this->refuses($date)) {
            return ManualPostingResult::preCutover();
        }

        $amount = Money::of($tx->amount);

        return $this->post(
            JournalDraft::make($date)
                ->from(SourceType::OWNER_WITHDRAWAL, $tx->id, $tx->note)
                ->key($this->nextKey(SourceType::OWNER_WITHDRAWAL, $tx->id))
                ->about($tx->note ?: 'Owner withdrawal')
                ->actor($tx->created_by)
                ->meta(['legacy' => 'fund_transactions'])
                ->debit(config('double-entry.manual.drawings_role', AccountRole::OWNER_DRAWINGS), $amount)
                ->credit($this->cashAccount(), $amount, 'Paid out')
        );
    }

    /**
     * The newest journal standing for any of these source types.
     */
    public function latestJournal(array $sourceTypes, int $sourceId): ?JournalEntry
    {
        if (!$sourceTypes) {
            return null;
        }

        return JournalEntry::query()
            ->whereIn('source_type', array_map(fn (SourceType $t) => $t->value, $sourceTypes))
            ->where('source_id', $sourceId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The journal that makes this row read-only, if there is one.
     *
     * A REVERSED journal does not block: after a reversal the row is legitimately
     * open again, and the corrected entry is owed to the books.
     */
    public function blockingJournal(array $sourceTypes, int $sourceId): ?JournalEntry
    {
        $latest = $this->latestJournal($sourceTypes, $sourceId);

        return $latest && $latest->status === JournalStatus::POSTED ? $latest : null;
    }

    /**
     * The books are silent about this row: it has never been journalled, or its
     * journal was reversed and a corrected entry is owed.
     */
    public function needsEntry(array $sourceTypes, int $sourceId): bool
    {
        $latest = $this->latestJournal($sourceTypes, $sourceId);

        return $latest === null || $latest->status === JournalStatus::REVERSED;
    }

    public function blockingJournalForExpense(Expense $expense): ?JournalEntry
    {
        return $this->blockingJournal([SourceType::EXPENSE], (int) $expense->id);
    }

    public function expenseNeedsEntry(Expense $expense): bool
    {
        return $this->needsEntry([SourceType::EXPENSE], (int) $expense->id);
    }

    /**
     * @return array<int, SourceType> the source types this row could have posted as
     */
    public function fundSourceTypes(FundTransaction $tx): array
    {
        // Only the two manual screens post journals. Everything else is a
        // system-linked row whose accounting belongs to its own module (and to a
        // later stage), so it must never be journalled from here.
        if (!in_array($tx->source, ['manual_add', 'withdraw'], true)) {
            return [];
        }

        // Keyed on the direction the money actually moved, not on which screen
        // created the row: the legacy edit form can change direction.
        return $tx->direction === 'in'
            ? [SourceType::OWNER_CAPITAL, SourceType::INCOME]
            : [SourceType::OWNER_WITHDRAWAL];
    }

    public function blockingJournalForFund(FundTransaction $tx): ?JournalEntry
    {
        return $this->blockingJournal($this->fundSourceTypes($tx), (int) $tx->id);
    }

    public function fundNeedsEntry(FundTransaction $tx): bool
    {
        $types = $this->fundSourceTypes($tx);

        return $types !== [] && $this->needsEntry($types, (int) $tx->id);
    }

    /**
     * Which side of equity/income a money-in row was booked as, so a corrected
     * entry defaults to what the operator said last time rather than making them
     * re-decide. Null when the row has never been journalled.
     */
    public function fundNature(FundTransaction $tx): ?string
    {
        return $this->naturesFor([(int) $tx->id])[(int) $tx->id] ?? null;
    }

    /**
     * The nature each money-in row was booked as, read back from its journal
     * metadata. One query for a whole page of rows, newest journal winning.
     *
     * @param array<int, int> $sourceIds
     * @return array<int, string> fund_transaction_id => owner_capital|other_income
     */
    public function naturesFor(array $sourceIds): array
    {
        if (!$sourceIds) {
            return [];
        }

        return JournalEntry::query()
            ->whereIn('source_type', [SourceType::OWNER_CAPITAL->value, SourceType::INCOME->value])
            ->whereIn('source_id', $sourceIds)
            ->orderByDesc('id')
            ->get(['id', 'source_id', 'metadata'])
            ->reduce(function (array $carry, JournalEntry $journal) {
                $nature = is_array($journal->metadata) ? ($journal->metadata['nature'] ?? null) : null;

                if ($nature !== null) {
                    $carry[(int) $journal->source_id] ??= (string) $nature;
                }

                return $carry;
            }, []);
    }

    /**
     * True when a source row already has any journal at all — the guard the
     * delete path needs, since deleting a row whose journal still stands would
     * orphan real accounting.
     */
    public function hasJournal(array $sourceTypes, int $sourceId): bool
    {
        return JournalEntry::query()
            ->whereIn('source_type', array_map(fn (SourceType $t) => $t->value, $sourceTypes))
            ->where('source_id', $sourceId)
            ->exists();
    }

    /**
     * Why the screen just refused, in the operator's terms rather than ours.
     */
    public static function refusalReason(JournalEntry $journal, string $what = 'This record'): string
    {
        return "{$what} is already posted in the books as {$journal->journal_no}. Posted journals are never edited or deleted — reverse {$journal->journal_no} in Accounting → Journals first, then correct the record here.";
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk sync: send the Lite money rows into the books, and re-open them
    |--------------------------------------------------------------------------
    */

    /**
     * The source types that originate from a Lite money row (expense screen and
     * the two manual fund screens). Nothing else may be pushed from here: sales,
     * purchases and system payments belong to their own modules and their own
     * posting rules, which are not this screen's to invent.
     */
    public const LITE_SOURCE_TYPES = [
        SourceType::EXPENSE,
        SourceType::OWNER_CAPITAL,
        SourceType::INCOME,
        SourceType::OWNER_WITHDRAWAL,
    ];

    /**
     * Lite rows that are still owed a journal, grouped by kind.
     *
     * "Owed" means there is no *standing* posted journal for the row: one that
     * was reversed re-opens it (the corrected entry is due), and one that never
     * posted — because it predates the bridge, or a posting failed — is simply
     * missed. Rows dated before the cutover are left out: they are inside the
     * opening balances already, exactly as the single-row writers refuse them.
     *
     * @return array{expenses:\Illuminate\Support\Collection, withdrawals:\Illuminate\Support\Collection, money_ins:\Illuminate\Support\Collection, totals:array<string,string>, cutover:?string}
     */
    public function pending(int $limit = 1000): array
    {
        $since = $this->cutoverDate();

        $expenses = Expense::query()
            ->when($since, fn ($q) => $q->whereDate('expense_date', '>=', $since))
            ->whereNotIn('id', $this->postedSourceIds([SourceType::EXPENSE]))
            ->orderBy('expense_date')->orderBy('id')
            ->limit($limit)->get();

        $withdrawals = FundTransaction::query()
            ->where('direction', 'out')->where('source', 'withdraw')
            ->when($since, fn ($q) => $q->whereDate('created_at', '>=', $since))
            ->whereNotIn('id', $this->postedSourceIds([SourceType::OWNER_WITHDRAWAL]))
            ->orderBy('created_at')->orderBy('id')
            ->limit($limit)->get();

        $moneyIns = FundTransaction::query()
            ->where('direction', 'in')->where('source', 'manual_add')
            ->when($since, fn ($q) => $q->whereDate('created_at', '>=', $since))
            ->whereNotIn('id', $this->postedSourceIds([SourceType::OWNER_CAPITAL, SourceType::INCOME]))
            ->orderBy('created_at')->orderBy('id')
            ->limit($limit)->get();

        return [
            'expenses'    => $expenses,
            'withdrawals' => $withdrawals,
            'money_ins'   => $moneyIns,
            'totals'      => [
                'expenses'    => Money::format(Money::sum($expenses->map(fn ($e) => (string) $e->amount)->all())),
                'withdrawals' => Money::format(Money::sum($withdrawals->map(fn ($t) => (string) $t->amount)->all())),
                'money_ins'   => Money::format(Money::sum($moneyIns->map(fn ($t) => (string) $t->amount)->all())),
            ],
            'cutover'     => $since,
        ];
    }

    /**
     * Post every owed row in one pass, reusing the exact per-row rules (key,
     * cutover, category) the single screens already use — this adds nothing to
     * the mapping, so a row synced here is indistinguishable from one posted at
     * the money screen. A row is never double-booked: the idempotent key means a
     * second pass simply re-hits the journal that already stands.
     *
     * @param  string|null  $moneyInNature  how to book money-in rows that have no
     *         recorded nature yet (owner_capital|other_income). Null means such
     *         rows are reported back rather than guessed, because the choice
     *         moves money between equity and profit.
     * @return array{posted:int, failed:int, pre_cutover:int, needs_nature:int}
     */
    public function syncAll(?string $moneyInNature = null): array
    {
        $pending = $this->pending();
        $known   = $this->naturesFor($pending['money_ins']->pluck('id')->all());

        $out = ['posted' => 0, 'failed' => 0, 'pre_cutover' => 0, 'needs_nature' => 0];

        foreach ($pending['expenses'] as $expense) {
            $this->tally($out, $this->expense($expense));
        }

        foreach ($pending['withdrawals'] as $tx) {
            $this->tally($out, $this->withdrawal($tx));
        }

        foreach ($pending['money_ins'] as $tx) {
            $nature = $known[$tx->id] ?? $moneyInNature;

            if ($nature === null) {
                $out['needs_nature']++;

                continue;
            }

            $this->tally($out, $this->moneyIn($tx, $nature));
        }

        return $out;
    }

    /**
     * Reverse every standing journal that came from a Lite money row, so those
     * rows re-open and may be synced again. Deliberately reversal-only: the
     * originals stay on the record (posted journals are immutable), and only the
     * four Lite source types are touched — the chart, opening balances and any
     * hand-built or event-driven journal are none of this button's business.
     *
     * @return array{reversed:int, failed:int, errors:array<int,string>}
     */
    public function resetLiteJournals(string $reason, ?int $actorId = null): array
    {
        $journals = JournalEntry::query()
            ->whereIn('source_type', array_map(fn (SourceType $t) => $t->value, self::LITE_SOURCE_TYPES))
            ->where('status', JournalStatus::POSTED->value)
            ->whereNull('reversal_of_id')
            ->orderBy('id')
            ->get();

        $out = ['reversed' => 0, 'failed' => 0, 'errors' => []];

        foreach ($journals as $journal) {
            try {
                $this->reversals->reverse($journal, $reason, $actorId);
                $out['reversed']++;
            } catch (\Throwable $e) {
                // A reversal can legitimately be refused (e.g. the as-at date
                // falls before cutover). One stuck journal must not abandon the
                // rest, and none of it may look like it silently succeeded.
                $out['failed']++;
                $out['errors'][] = "{$journal->journal_no}: {$e->getMessage()}";
            }
        }

        return $out;
    }

    /**
     * @param  array<int, SourceType>  $types
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function postedSourceIds(array $types)
    {
        return JournalEntry::query()
            ->whereIn('source_type', array_map(fn (SourceType $t) => $t->value, $types))
            ->where('status', JournalStatus::POSTED->value)
            ->whereNotNull('source_id')
            ->pluck('source_id');
    }

    protected function cutoverDate(): ?string
    {
        $cutover = (string) config('double-entry.cutover_date');

        return $cutover === '' || $cutover === 'null' ? null : $cutover;
    }

    /**
     * @param  array<string, int>  $out
     */
    protected function tally(array &$out, ManualPostingResult $result): void
    {
        if ($result->isPosted()) {
            $out['posted']++;
        } elseif ($result->status === ManualPostingResult::PRE_CUTOVER) {
            $out['pre_cutover']++;
        } else {
            $out['failed']++;
        }
    }

    protected function post(JournalDraft $draft): ManualPostingResult
    {
        $journal = $this->failures->attempt($draft);

        return $journal ? ManualPostingResult::posted($journal->journal_no) : ManualPostingResult::failed();
    }

    /**
     * Events before the cutover are inside the opening balances already.
     */
    protected function refuses(string $date): bool
    {
        $cutover = (string) config('double-entry.cutover_date');

        return $cutover !== '' && $cutover !== 'null' && $date < $cutover;
    }

    /**
     * Free-text category to accounting role.
     *
     * The map is configuration, not code: a new spelling is a config line, and an
     * unknown one still lands on a real expense account instead of failing silently.
     */
    protected function roleForCategory(?string $category): string
    {
        $map = (array) config('double-entry.manual.expense_roles', []);

        $key = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $category));
        $key = trim($key, '_');

        if ($key !== '' && isset($map[$key])) {
            return $map[$key];
        }

        return config('double-entry.manual.expense_default_role', AccountRole::GENERAL_EXPENSE);
    }

    /**
     * The account cash physically moves through: the mapped fund when there is
     * one, plain cash otherwise. Returned as a model so the registry does not
     * re-resolve it by role.
     */
    public function cashAccount(): Account|string
    {
        $fund = FundAccount::forFund(config('double-entry.manual.fund_key'));

        if ($fund?->account) {
            return $fund->account;
        }

        return AccountRole::CASH;
    }

    /**
     * Stable per-source posting key. The first journal for a row is `expense:12`;
     * a corrected entry after a reversal is `expense:12:v2`. While a journal is
     * still standing the key points at IT, so replaying a request can only hit
     * the unique key and return the journal that already exists — never book the
     * same money twice.
     */
    protected function nextKey(SourceType $sourceType, int $sourceId): string
    {
        $base = "{$sourceType->value}:{$sourceId}";

        $journals = JournalEntry::query()
            ->where('source_type', $sourceType->value)
            ->where('source_id', $sourceId)
            ->orderByDesc('id')
            ->get(['id', 'status', 'posting_key']);

        if ($journals->isEmpty()) {
            return $base;
        }

        $latest = $journals->first();

        if ($latest->status !== JournalStatus::REVERSED) {
            return $latest->posting_key ?: $base;
        }

        return "{$base}:v" . ($journals->count() + 1);
    }
}
