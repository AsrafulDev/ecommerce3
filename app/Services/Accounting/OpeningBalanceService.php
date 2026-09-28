<?php

namespace App\Services\Accounting;

use App\Models\FundTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Exceptions\AccountingException;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\AccountRegistry;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\JournalLineDraft;
use Softmit\DoubleEntry\Support\Money;

/**
 * The one journal that brings the old records into the books.
 *
 * Nothing here is silently trusted. Every line the system can work out is
 * derived from a named query, shown to the operator WITH the reason it might be
 * wrong, and editable before it is booked. Where the application holds two
 * different answers to the same question — it does, in several places — both are
 * reported instead of a pick being made in code, because only a human knows which
 * one is real money.
 *
 * The worksheet works on a DRAFT journal, which no report reads, so a half-built
 * opening balance cannot mislead anyone. Posting is a separate, deliberate act,
 * and the unique posting key means it can happen exactly once.
 */
class OpeningBalanceService
{
    /** One opening journal for the whole business, so it cannot be applied twice. */
    public const POSTING_KEY = 'opening:balances';

    /** @var array<int, string> order states whose residual due is not a receivable */
    private const DEAD_ORDER_STATUSES = ['cancelled', 'closed', 'returned', 'return_approved'];

    private const DEAD_PAYMENT_STATUSES = ['refunded', 'failed', 'cancelled'];

    public function __construct(
        protected JournalPoster $poster,
        protected AccountRegistry $registry,
        protected ManualEntryService $manual,
    ) {}

    /**
     * Balances are brought forward on the LAST day before the books start, so the
     * first report run on the cutover date shows them as opening rather than as
     * trading activity.
     */
    public function asAtDate(): string
    {
        $cutover = (string) config('double-entry.cutover_date');

        if ($cutover === '' || $cutover === 'null') {
            return now()->format('Y-m-d');
        }

        return Carbon::parse($cutover)->subDay()->toDateString();
    }

    public function draft(): ?JournalEntry
    {
        return JournalEntry::where('posting_key', self::POSTING_KEY)
            ->where('status', JournalStatus::DRAFT)
            ->orderByDesc('id')
            ->first();
    }

    public function posted(): ?JournalEntry
    {
        return JournalEntry::where('posting_key', self::POSTING_KEY)
            ->whereIn('status', [JournalStatus::POSTED, JournalStatus::REVERSED])
            ->orderByDesc('id')
            ->first();
    }

    public function isSettled(): bool
    {
        return $this->posted() !== null;
    }

    /**
     * The saved draft, in the same shape the worksheet works with, so an operator
     * can reopen a half-finished opening balance and keep editing it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function editorLines(?JournalEntry $draft): array
    {
        if (!$draft) {
            return [];
        }

        $lines = $draft->relationLoaded('lines')
            ? $draft->getRelation('lines')
            : $draft->lines()->get();

        return $lines->map(fn ($line) => [
            'account_id'  => (int) $line->account_id,
            'description' => (string) $line->description,
            'debit'       => (string) $line->debit,
            'credit'      => (string) $line->credit,
            'party_type'  => $line->party_type instanceof PartyType ? $line->party_type->value : $line->party_type,
            'party_id'    => $line->party_id,
            'source'      => null,
        ])->all();
    }

    /**
     * What the old records say the business is worth, line by line.
     *
     * @return array{lines: array<int, array<string, mixed>>, warnings: array<int, string>, missing: array<int, string>}
     */
    public function derive(): array
    {
        $asAt = $this->asAtDate();
        $lines = [];
        $warnings = [];
        $missing = [];

        // ── Money actually held ───────────────────────────────────────────────
        $collected = Money::subtract($this->moneyIn($asAt, collected: true), $this->moneyOut($asAt));
        $reported = Money::subtract($this->moneyIn($asAt, collected: false), $this->moneyOut($asAt));

        // The fund can only ever be over-drawn because the app lets money out be
        // recorded against cash-on-delivery that has not arrived. That is a real
        // liability, not a negative asset, so it must not be posted as one.
        if (Money::isNegative($collected)) {
            $warnings[] = sprintf(
                '%s more has been paid out of the fund than has been collected into it. Cash cannot be negative: the line below is a CREDIT of %s (money the business is short), and you must decide whether that gap is uncollected delivery money or money that left without being recorded.',
                Money::format(Money::negate($collected)),
                Money::format(Money::negate($collected))
            );
        }

        // A zero row cannot be saved (an account with no amount is refused), so a
        // nil figure is reported as a question instead of being pushed into the
        // worksheet as a line the operator then has to delete.
        if (Money::isZero($collected)) {
            $warnings[] = "The fund records net to exactly 0.00 as at {$asAt}. Either the fund really was empty, or money moved without being recorded — add the cash line yourself if the till or the bank holds anything.";
        } else {
            $lines[] = $this->line(
                $this->manual->cashAccount(),
                Money::isNegative($collected) ? '0.00' : $collected,
                Money::isNegative($collected) ? Money::negate($collected) : '0.00',
                'Cash and funds on hand',
                "fund_transactions up to {$asAt}: money actually received, less everything paid out"
            );
        }

        if (!Money::equals($collected, $reported)) {
            $warnings[] = sprintf(
                'The fund screen shows %s, but only %s has actually been collected — the rest is cash-on-delivery orders counted before the money arrived. The derivation used the collected figure. Add the difference as a line if the till and the bank really hold it.',
                Money::format($reported),
                Money::format($collected)
            );
        }

        $missing[] = 'The records hold one undifferentiated fund: no row says how much is physical cash, how much is at the bank and how much is in a mobile wallet. Split the cash figure into separate Cash / Bank / Mobile lines if you know the split.';

        // ── What customers owe (a line per customer, so their statements work) ─
        [$arLines, $arWarnings] = $this->receivableLines($asAt);
        $lines = array_merge($lines, $arLines);
        $warnings = array_merge($warnings, $arWarnings);

        // ── Stock on hand ─────────────────────────────────────────────────────
        $stock = $this->stockValue($asAt);
        $cached = $this->stockValueFromProductCache();

        if (Money::isZero($stock)) {
            $warnings[] = "The batch records say there was no stock on hand at all as at {$asAt}. If the warehouse was not empty, purchases were recorded outside the batch screen — add the stock line yourself at what it cost you.";
        } else {
            $lines[] = $this->line(
                AccountRole::INVENTORY,
                $stock,
                '0.00',
                'Stock on hand at cost',
                "SUM(remaining_qty × unit_cost) over stock_batches up to {$asAt} — the batch records, which are the app's stock truth"
            );
        }

        if (!Money::equals($stock, $cached)) {
            $warnings[] = sprintf(
                'Counting the batches gives %s; the older per-product cost cache gives %s. The batch figure is used because the product column is a cache that drifts. Reconcile the difference before you approve — it is either un-synced stock or stock that no longer exists.',
                Money::format($stock),
                Money::format($cached)
            );
        }

        // ── What we owe suppliers ────────────────────────────────────────────
        [$apLines, $apWarnings] = $this->payableLines($asAt);
        $lines = array_merge($lines, $apLines);
        $warnings = array_merge($warnings, $apWarnings);

        // ── Salary owed to staff ─────────────────────────────────────────────
        foreach ($this->salaryOwed($asAt) as $row) {
            $lines[] = $this->line(
                AccountRole::EMPLOYEE_PAYABLE,
                '0.00',
                $row['amount'],
                "Salary payable — {$row['name']}",
                "employee_salaries (calculated, not yet paid) for {$row['name']}",
                PartyType::EMPLOYEE,
                $row['id']
            );
        }

        $bonus = $this->approvedUnpaidBonus($asAt);
        if (!Money::isZero($bonus)) {
            $warnings[] = sprintf(
                '%s of bonuses are approved but unpaid. An approved bonus is also folded into a calculated salary by the payroll screen, so it may already be in the salary line above — do not book both. Keep whichever one is true and delete the other.',
                Money::format($bonus)
            );
        }

        // ── Refunds promised but not yet paid ────────────────────────────────
        $refunds = $this->pendingRefunds($asAt);
        if (!Money::isZero($refunds)) {
            $lines[] = $this->line(
                AccountRole::OTHER_PAYABLE,
                '0.00',
                $refunds,
                'Refunds approved/pending but not paid out',
                "refunds still awaiting payment up to {$asAt}"
            );

            $warnings[] = 'A pending refund may already be sitting inside a customer\'s due amount — the app does not link the two. Check any customer who appears on both lists and remove the overlap, or you will owe it twice.';
        }

        $missing[] = 'Employee advances have no table in this application at all, so nothing can be worked out. If staff hold company money, add the line yourself.';
        $missing[] = 'Courier bills are never recorded — the only delivery figures kept are quoted rates per order, not what Pathao / Steadfast / RedX have billed you. Add what you owe each courier yourself.';
        $missing[] = 'Warranty claims record what was charged to the supplier and to the customer but not whether either has been settled, so warranty receivables and payables cannot be worked out. Add them by hand if there are any open.';
        $missing[] = 'Anything held outside this application — a second bank account, a vehicle, furniture, a loan, money the owner took out that was never recorded — has to be added as a line. If you leave it out, the balancing figure will quietly absorb it as owner capital.';

        return ['lines' => $lines, 'warnings' => $warnings, 'missing' => $missing];
    }

    /**
     * @return array{debit: string, credit: string, difference: string, lines: int}
     */
    public function totals(array $lines): array
    {
        $debit = Money::sum(array_map(fn ($l) => (string) ($l['debit'] ?? '0.00'), $lines));
        $credit = Money::sum(array_map(fn ($l) => (string) ($l['credit'] ?? '0.00'), $lines));

        return [
            'debit'      => $debit,
            'credit'     => $credit,
            'difference' => Money::subtract($debit, $credit),
            'lines'      => count($lines),
        ];
    }

    /**
     * The line that would make the worksheet balance. Offered, never applied
     * automatically: a plug into equity is a claim about the owner's money, and
     * an invisible one is how a mistake becomes permanent.
     */
    public function balancingLine(array $lines, ?string $role = null): ?array
    {
        $difference = $this->totals($lines)['difference'];

        if (Money::isZero($difference)) {
            return null;
        }

        $role = $role ?: config('double-entry.opening.balancing_role', AccountRole::RETAINED_EARNINGS);

        // The plug is always a positive amount on whichever side is short: moving
        // the difference to the other side with its sign intact would just be the
        // same mistake written twice.
        $amount = Money::isPositive($difference) ? $difference : Money::negate($difference);

        return $this->line(
            $role,
            Money::isPositive($difference) ? '0.00' : $amount,
            Money::isPositive($difference) ? $amount : '0.00',
            'Balancing figure — what the owner has in the business after everything above',
            'plug: the difference between the debit and credit lines above'
        );
    }

    /**
     * Store the worksheet as the draft opening journal, replacing any earlier draft.
     *
     * @param array<int, array<string, mixed>> $lines
     */
    public function saveDraft(array $lines, ?string $asAt = null, ?int $actorId = null): JournalEntry
    {
        if ($journal = $this->posted()) {
            throw new AccountingException(
                "Opening balances are already posted as {$journal->journal_no}. They cannot be rewritten — reverse that journal and post a corrected opening instead."
            );
        }

        $clean = $this->clean($lines);
        $this->assertLinesAreUsable($clean);
        if ($existing = $this->draft()) {
            $existing->delete();
        }

        $draft = $this->draftFor($clean, $asAt ?: $this->asAtDate(), $actorId);

        return $this->poster->save($draft);
    }

    /**
     * Book the opening balances. An unbalanced worksheet never reaches the ledger —
     * the poster refuses it and the draft stays where it is.
     */
    public function post(JournalEntry $draft): JournalEntry
    {
        if ($this->isSettled()) {
            throw new AccountingException('Opening balances have already been posted once.');
        }

        $posted = $this->poster->postDraft($draft);

        log_activity(
            'accounting',
            'create',
            "Opening balances posted as {$posted->journal_no}: ".
            Money::format($posted->total_debit).' across '.$posted->lines->count().' lines',
            $posted,
            ['journal_id' => $posted->id, 'transaction_date' => $posted->transaction_date]
        );

        return $posted;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    protected function draftFor(array $lines, string $date, ?int $actorId): JournalDraft
    {
        $draft = JournalDraft::make($date)
            ->from(SourceType::OPENING, null, "Opening balances as at {$date}")
            ->key(self::POSTING_KEY)
            ->about("Opening balances brought forward from the previous records as at {$date}")
            ->actor($actorId)
            ->approvedBy($actorId)
            ->meta([
                'legacy'  => 'opening-balances',
                'as_at'   => $date,
                'derived' => array_values(array_filter(array_map(fn ($l) => $l['source'] ?? null, $lines))),
            ]);

        foreach ($lines as $line) {
            $draft->line(new JournalLineDraft(
                $line['account_id'],
                (string) $line['debit'],
                (string) $line['credit'],
                $line['description'],
                $line['party_type'] ?? null,
                $line['party_id'] ?? null,
            ));
        }

        return $draft;
    }

    /**
     * Drop the empty rows the editor always posts, and normalise what is left so
     * a typed "1,200.50" cannot reach the ledger as text and a role name cannot
     * reach it as anything but the account it stands for.
     *
     * @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    protected function clean(array $lines): array
    {
        $clean = [];

        foreach (array_values($lines) as $i => $line) {
            // Humans count from one, the form does not: the error key has to match
            // lines[N] in the worksheet or the message lands nowhere and the row
            // looks fine while the save silently fails.
            $row = $i + 1;
            $key = "lines.{$i}";
            $debit = Money::of($line['debit'] ?? '0.00');
            $credit = Money::of($line['credit'] ?? '0.00');

            $description = trim((string) ($line['description'] ?? ''));

            if (Money::isZero($debit) && Money::isZero($credit) && $description === '') {
                continue;
            }

            if (empty($line['account_id'])) {
                throw ValidationException::withMessages(["{$key}.account_id" => "Line {$row} has no account."]);
            }

            try {
                $account = $this->registry->resolve(
                    is_numeric($line['account_id']) ? (int) $line['account_id'] : $line['account_id'],
                    "opening balance line {$row}"
                );
            } catch (AccountingException $e) {
                throw ValidationException::withMessages(["{$key}.account_id" => "Line {$row}: ".$e->getMessage()]);
            }

            if (!Money::isZero($debit) && !Money::isZero($credit)) {
                throw ValidationException::withMessages(["{$key}.debit" => "Line {$row} has both a debit and a credit. A line moves one way only."]);
            }

            if (Money::isNegative($debit) || Money::isNegative($credit)) {
                throw ValidationException::withMessages(["{$key}.debit" => "Line {$row} is negative. Move the amount to the other side instead."]);
            }

            if (Money::isZero($debit) && Money::isZero($credit)) {
                throw ValidationException::withMessages(["{$key}.debit" => "Line {$row} has an account but no amount."]);
            }

            $partyId = $line['party_id'] ?? null;

            $clean[] = [
                'account_id'  => $account->id,
                'description' => $description ?: 'Opening balance',
                'debit'       => $debit,
                'credit'      => $credit,
                'party_type'  => $this->partyType($line['party_type'] ?? null, $partyId),
                'party_id'    => $partyId !== null && $partyId !== '' ? (int) $partyId : null,
                'source'      => $line['source'] ?? null,
            ];
        }

        return $clean;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    protected function assertLinesAreUsable(array $lines): void
    {
        if (!$lines) {
            throw ValidationException::withMessages(['lines' => 'There is nothing to save — add at least one line with an amount.']);
        }
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    protected function receivableLines(string $asAt): array
    {
        if (!Schema::hasColumn('orders', 'due_amount')) {
            return [[], ['The orders table has no due_amount column on this database, so receivables could not be worked out. Add the line yourself.']];
        }

        $rows = DB::table('orders')
            ->selectRaw('customer_id, SUM(due_amount) as due, COUNT(*) as orders')
            ->where('due_amount', '>', 0)
            ->whereNotIn('order_status', self::DEAD_ORDER_STATUSES)
            ->whereNotIn('payment_status', self::DEAD_PAYMENT_STATUSES)
            ->whereDate('created_at', '<=', $asAt)
            ->groupBy('customer_id')
            ->orderByDesc('due')
            ->get();

        $names = $this->names('customers', $rows->pluck('customer_id')->filter()->all());

        $lines = [];
        foreach ($rows as $row) {
            $amount = Money::of($row->due);
            $guest = empty($row->customer_id);
            $customer = $names[$row->customer_id] ?? ('customer #'.$row->customer_id);

            $lines[] = $this->line(
                AccountRole::ACCOUNTS_RECEIVABLE,
                $amount,
                '0.00',
                $guest
                    ? "Orders still owed to us — not linked to a customer ({$row->orders} orders)"
                    : "Orders still owed by {$customer} ({$row->orders} orders)",
                "SUM(orders.due_amount) for customer {$row->customer_id}, live orders only, up to {$asAt}",
                $guest ? null : PartyType::CUSTOMER,
                $guest ? null : (int) $row->customer_id,
            );
        }

        $warnings = [];

        $dead = DB::table('orders')
            ->where('due_amount', '>', 0)
            ->whereIn('order_status', self::DEAD_ORDER_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(due_amount) as due')
            ->first();

        if ((int) $dead->orders > 0) {
            $warnings[] = sprintf(
                '%s of due is sitting on %d cancelled, returned or closed orders. Those are NOT treated as money owed to you — a cancelled order whose due was never cleared is a data error, not a receivable. If any of it really is collectable, add it as a line.',
                Money::format(Money::of($dead->due)),
                (int) $dead->orders
            );
        }

        return [$lines, $warnings];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    protected function payableLines(string $asAt): array
    {
        if (!Schema::hasColumn('suppliers', 'current_due')) {
            return [[], ['The suppliers table has no current_due column on this database, so payables could not be worked out. Add the line yourself.']];
        }

        $rows = DB::table('suppliers')
            ->where('current_due', '>', 0)
            ->orderByDesc('current_due')
            ->get(['id', 'name', 'current_due']);

        $names = $this->names('suppliers', $rows->pluck('id')->all());

        $lines = [];
        foreach ($rows as $row) {
            $supplier = $names[$row->id] ?? ('supplier #'.$row->id);

            $lines[] = $this->line(
                AccountRole::ACCOUNTS_PAYABLE,
                '0.00',
                Money::of($row->current_due),
                "Owed to {$supplier}",
                "suppliers.current_due for supplier {$row->id}",
                PartyType::SUPPLIER,
                (int) $row->id,
            );
        }

        $warnings = [];

        if (Schema::hasColumn('purchases', 'due_amount')) {
            $fromDocuments = Money::of(DB::table('purchases')->where('due_amount', '>', 0)->sum('due_amount'));
            $running = Money::sum($rows->pluck('current_due')->map(fn ($v) => Money::of($v))->all());

            if (!Money::equals($fromDocuments, $running)) {
                $warnings[] = sprintf(
                    'The supplier list says %s is owed; the purchase documents say %s. The running total on the supplier row is floored at zero whenever a return would push it negative, so a supplier credit can vanish there. Neither number can be trusted on its own — reconcile them and use the one you can defend.',
                    Money::format($running),
                    Money::format($fromDocuments)
                );
            }
        }

        return [$lines, $warnings];
    }

    protected function moneyIn(string $asAt, bool $collected): string
    {
        $query = FundTransaction::where('direction', 'in')->whereDate('created_at', '<=', $asAt);

        if ($collected) {
            // A 'sale' row is only real money once the order is actually paid.
            $paidOrderIds = DB::table('orders')->where('payment_status', 'paid')->pluck('id')->all();

            $query->where(function ($q) use ($paidOrderIds) {
                $q->where('source', '!=', 'sale');

                if ($paidOrderIds) {
                    $q->orWhere(function ($sub) use ($paidOrderIds) {
                        $sub->where('source', 'sale')->whereIn('source_id', $paidOrderIds);
                    });
                }
            });
        }

        return Money::of($query->sum('amount'));
    }

    protected function moneyOut(string $asAt): string
    {
        return Money::of(
            FundTransaction::where('direction', 'out')->whereDate('created_at', '<=', $asAt)->sum('amount')
        );
    }

    protected function stockValue(string $asAt): string
    {
        return Money::of(
            DB::table('stock_batches')
                ->where('remaining_qty', '>', 0)
                ->whereDate('created_at', '<=', $asAt)
                ->sum(DB::raw('remaining_qty * unit_cost'))
        );
    }

    /**
     * The older way the app valued stock, kept only so the two can be compared.
     */
    protected function stockValueFromProductCache(): string
    {
        if (!Schema::hasColumn('products', 'purchase_price') || !Schema::hasColumn('products', 'stock')) {
            return Money::zero();
        }

        return Money::of(DB::table('products')->sum(DB::raw('stock * purchase_price')));
    }

    /**
     * @return array<int, array{id: int, name: string, amount: string}>
     */
    protected function salaryOwed(string $asAt): array
    {
        if (!Schema::hasTable('employee_salaries')) {
            return [];
        }

        $rows = DB::table('employee_salaries')
            ->where('status', 'calculated')
            ->whereDate('created_at', '<=', $asAt)
            ->groupBy('employee_id')
            ->orderBy('employee_id')
            ->get(['employee_id', DB::raw('SUM(net_salary) as net')]);

        $names = $this->names('employees', $rows->pluck('employee_id')->filter()->all());

        return $rows->map(fn ($row) => [
            'id'     => (int) $row->employee_id,
            'name'   => $names[$row->employee_id] ?? ('employee #'.$row->employee_id),
            'amount' => Money::of($row->net),
        ])->all();
    }

    protected function approvedUnpaidBonus(string $asAt): string
    {
        if (!Schema::hasTable('employee_bonuses')) {
            return Money::zero();
        }

        return Money::of(
            DB::table('employee_bonuses')->where('status', 'approved')->whereDate('created_at', '<=', $asAt)->sum('amount')
        );
    }

    /**
     * A refund's true size depends on three columns and the app reads all of them
     * differently, so the same rule the refund screen uses is applied here rather
     * than a new one being invented.
     */
    protected function pendingRefunds(string $asAt): string
    {
        if (!Schema::hasTable('refunds')) {
            return Money::zero();
        }

        $rows = DB::table('refunds')
            ->where('status', 'pending')
            ->whereDate('created_at', '<=', $asAt)
            ->get(['amount', 'shipping_charge', 'refund_amount', 'include_shipping']);

        return Money::sum($rows->map(function ($row) {
            if ($row->refund_amount !== null) {
                return Money::of($row->refund_amount);
            }

            $amount = Money::of($row->amount);

            return $row->include_shipping ? Money::add($amount, Money::of($row->shipping_charge)) : $amount;
        })->all());
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, string>
     */
    protected function names(string $table, array $ids): array
    {
        if (!$ids || !Schema::hasColumn($table, 'name')) {
            return [];
        }

        return DB::table($table)->whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    protected function partyType(mixed $type, mixed $partyId): ?PartyType
    {
        if ($partyId === null || $partyId === '' || !$type) {
            return null;
        }

        return $type instanceof PartyType ? $type : PartyType::tryFrom((string) $type);
    }

    /**
     * One worksheet row. Amounts are decimal strings the moment they are built —
     * a float never gets in — and an account is reduced to something the screen
     * can put in a select box, so a missing role shows up as a problem instead of
     * crashing the worksheet.
     */
    protected function line(
        string|Account $account,
        string $debit,
        string $credit,
        string $description,
        ?string $source = null,
        ?PartyType $partyType = null,
        ?int $partyId = null,
    ): array {
        return [
            'account_id'  => $this->accountRef($account),
            'description' => $description,
            'debit'       => $debit,
            'credit'      => $credit,
            'party_type'  => $partyType?->value,
            'party_id'    => $partyId,
            'source'      => $source,
        ];
    }

    protected function accountRef(string|Account $account): int|string
    {
        if ($account instanceof Account) {
            return $account->id;
        }

        try {
            return $this->registry->idFor($account);
        } catch (AccountingException) {
            // No active account carries this role. Hand the name back so the screen
            // can say so, rather than dropping the amount off the worksheet.
            return $account;
        }
    }
}
