<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\OrderPayment;
use App\Support\Accounting\AccountingAvailability;
use App\Support\Accounting\ManualPostingResult;
use Softmit\DoubleEntry\Enums\SourceType;

/**
 * The live adapter: turns business operations into double-entry postings by
 * delegating to the integration-layer services (ManualEntryService today, the
 * accrual bridge for sales/purchases/payments as those integrations land).
 *
 * This file — not any commerce controller — is where Softmit\DoubleEntry
 * imports belong. The container binds it only when AccountingAvailability
 * reports available() AND enabled(), so ordinary Lite traffic never
 * constructs this class, ManualEntryService, or any package service.
 *
 * Posting failures never escape into the commerce flow: ManualEntryService
 * routes them through the package failure log and reports them as
 * ManualPostingResult::FAILED, so the operator sees the books disagree rather
 * than being quietly misled. Disabled is not the same as failed — when this
 * gateway is bound, a null/true/false answer from a method is a real answer.
 */
final class DoubleEntryAdvancedAccountingGateway implements AdvancedAccountingGateway
{
    public function __construct(protected ManualEntryService $manual)
    {
    }

    public function available(): bool
    {
        return AccountingAvailability::available();
    }

    public function enabled(): bool
    {
        return AccountingAvailability::enabled();
    }

    public function recordExpense(Expense $expense): ?ManualPostingResult
    {
        return $this->manual->expense($expense);
    }

    public function recordMoneyIn(FundTransaction $tx, string $nature): ?ManualPostingResult
    {
        return $this->manual->moneyIn($tx, $nature);
    }

    public function recordWithdrawal(FundTransaction $tx): ?ManualPostingResult
    {
        return $this->manual->withdrawal($tx);
    }

    public function expenseEditBlockReason(Expense $expense): ?string
    {
        $journal = $this->manual->blockingJournalForExpense($expense);

        return $journal ? ManualEntryService::refusalReason($journal, 'This expense') : null;
    }

    public function expenseHasJournal(Expense $expense): bool
    {
        return $this->manual->hasJournal([SourceType::EXPENSE], (int) $expense->id);
    }

    public function expenseNeedsEntry(Expense $expense): bool
    {
        return $this->manual->expenseNeedsEntry($expense);
    }

    public function fundEditBlockReason(FundTransaction $tx): ?string
    {
        $journal = $this->manual->blockingJournalForFund($tx);

        return $journal ? ManualEntryService::refusalReason($journal, 'This fund record') : null;
    }

    public function fundHasJournal(FundTransaction $tx): bool
    {
        return $this->manual->hasJournal($this->manual->fundSourceTypes($tx), (int) $tx->id);
    }

    public function fundNeedsEntry(FundTransaction $tx): bool
    {
        return $this->manual->fundNeedsEntry($tx);
    }

    public function fundNature(FundTransaction $tx): ?string
    {
        return $this->manual->fundNature($tx);
    }

    public function fundNaturesFor(array $sourceIds): array
    {
        return $this->manual->naturesFor($sourceIds);
    }

    /**
     * Cash-side accrual posting for collected payments is introduced with the
     * customer-payment integration phase; until then the Lite fund ledger
     * already holds the money, which is the operational truth.
     */
    public function paymentReceived(OrderPayment $payment): void
    {
    }
}
