<?php

namespace App\Services\TransactionReset;

use App\Models\Order;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Support\Accounting\AccountingAvailability;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Models\JournalLine;

/**
 * Checks the books, not the return value of a posting call. A sale is reversed in the accounts only when every
 * posted sale journal for it has a reversal with the same totals, and the ledger still balances afterwards.
 *
 * Nothing here posts or deletes a journal. When the books cannot be verified, the reset is refused and rolled
 * back, so no sale is ever reported as reversed while its journal is still posted.
 */
final class SaleAccountingVerifier
{
    public const NOT_APPLICABLE = 'not_applicable';

    public const VERIFIED = 'verified';

    /** @return list<JournalEntry>  the posted sale and sale-cost journals of this order */
    public function postedSaleJournals(Order $order): array
    {
        if (! AccountingAvailability::available()) {
            return [];
        }

        return JournalEntry::query()
            ->whereIn('source_type', [SourceType::SALE->value, SourceType::SALE_COGS->value])
            ->where('source_id', $order->id)
            ->where('status', JournalStatus::POSTED->value)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /** Why the accounts cannot reverse this sale right now. Empty when they can, or when there is nothing to reverse. */
    public function blockers(Order $order, AdvancedAccountingGateway $gateway): array
    {
        $journals = $this->postedSaleJournals($order);
        if ($journals === []) {
            return [];
        }

        if (! AccountingAvailability::enabled()) {
            return ['This sale has posted accounting journals, but Advanced accounting is off, so they cannot be reversed here.'];
        }
        if (! $gateway->readyForLivePosting()) {
            return ['Accounting is not ready for live posting (cutover incomplete), so this sale\'s journals cannot be reversed.'];
        }

        $sales = collect($journals)->filter(fn ($j) => $this->typeOf($j) === SourceType::SALE->value)->count();
        $costs = collect($journals)->filter(fn ($j) => $this->typeOf($j) === SourceType::SALE_COGS->value)->count();
        if ($sales > 1 || $costs > 1) {
            return ['More than one posted journal of the same kind exists for this sale; it needs reconciliation.'];
        }

        return [];
    }

    /**
     * Confirms, after the reversal ran, that the books show it: no sale journal is still posted, each original has
     * one posted reversal with the same totals, and the whole ledger balances. Throws when any of that fails.
     *
     * @param  list<JournalEntry>  $before  the posted journals captured before the reversal
     * @return array{status: string, journals: list<array{original: string, reversal: string, amount: float}>, ledger_balanced: bool}
     */
    public function verifyReversed(Order $order, array $before): array
    {
        if ($before === []) {
            return ['status' => self::NOT_APPLICABLE, 'journals' => [], 'ledger_balanced' => $this->ledgerBalanced()];
        }

        if ($this->postedSaleJournals($order) !== []) {
            throw new \DomainException('The accounting reversal did not complete: a sale journal is still posted. Nothing was changed.');
        }

        $journals = [];
        foreach ($before as $original) {
            $reversal = JournalEntry::query()
                ->where('reversal_of_id', $original->id)
                ->where('status', JournalStatus::POSTED->value)
                ->first();
            if (! $reversal) {
                throw new \DomainException("No posted reversal was found for journal {$original->journal_no}. Nothing was changed.");
            }

            [$debit, $credit] = $this->totals($reversal);
            [$originalDebit, $originalCredit] = $this->totals($original);
            if (abs($debit - $credit) > 0.005 || abs($debit - $originalDebit) > 0.005 || abs($credit - $originalCredit) > 0.005) {
                throw new \DomainException("The reversal of {$original->journal_no} does not mirror it. Nothing was changed.");
            }

            $journals[] = ['original' => (string) $original->journal_no, 'reversal' => (string) $reversal->journal_no, 'amount' => round($debit, 2)];
        }

        if (! $this->ledgerBalanced()) {
            throw new \DomainException('The ledger is not balanced after the reversal. Nothing was changed.');
        }

        return ['status' => self::VERIFIED, 'journals' => $journals, 'ledger_balanced' => true];
    }

    /** @return array{0: float, 1: float} */
    private function totals(JournalEntry $entry): array
    {
        $row = DB::table(JournalLine::TABLE)
            ->where('journal_entry_id', $entry->id)
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit, COALESCE(SUM(credit), 0) AS credit')
            ->first();

        return [(float) $row->debit, (float) $row->credit];
    }

    /** Every line of every posted or reversed journal must add up: total debits equal total credits. */
    private function ledgerBalanced(): bool
    {
        $entries = (new JournalEntry)->getTable();
        $row = DB::table(JournalLine::TABLE)
            ->join($entries, $entries.'.id', '=', JournalLine::TABLE.'.journal_entry_id')
            ->whereIn($entries.'.status', [JournalStatus::POSTED->value, JournalStatus::REVERSED->value])
            ->selectRaw('COALESCE(SUM('.JournalLine::TABLE.'.debit), 0) AS debit, COALESCE(SUM('.JournalLine::TABLE.'.credit), 0) AS credit')
            ->first();

        return abs((float) $row->debit - (float) $row->credit) < 0.01;
    }

    private function typeOf(JournalEntry $journal): string
    {
        $type = $journal->source_type;

        return $type instanceof \BackedEnum ? (string) $type->value : (string) $type;
    }
}
