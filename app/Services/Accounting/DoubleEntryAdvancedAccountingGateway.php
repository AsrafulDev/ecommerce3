<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Models\Refund;
use App\Models\Order;
use App\Support\Accounting\AccountingAvailability;
use App\Support\Accounting\ManualPostingResult;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Models\JournalEntry;

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

    public function readyForLivePosting(): bool
    {
        if (!$this->enabled() || !config('double-entry.cutover_date')) {
            return false;
        }

        $opening = JournalEntry::query()
            ->where('posting_key', \App\Services\Accounting\OpeningBalanceService::POSTING_KEY)
            ->where('status', JournalStatus::POSTED)
            ->latest('id')
            ->first();

        return $opening !== null
            && (string) $opening->total_debit === (string) $opening->total_credit;
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
    public function paymentReceived(OrderPayment $payment): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->customerPayment($payment);
    }

    public function recordSale(Order $order): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->sale($order);
    }

    public function recordCogs(Order $order): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->cogs($order);
    }

    public function recordPurchase(Purchase $purchase): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->purchase($purchase);
    }

    public function supplierPaymentMade(SupplierPayment $payment): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->supplierPayment($payment);
    }

    public function reverseSale(Order $order, string $reason, ?int $actorId = null): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->reverseSale($order, $reason, $actorId);
    }

    public function recordCustomerRefund(Refund $refund): ?ManualPostingResult
    {
        if (!$this->readyForLivePosting()) return ManualPostingResult::notReady();
        return $this->manual->customerRefund($refund);
    }
}
