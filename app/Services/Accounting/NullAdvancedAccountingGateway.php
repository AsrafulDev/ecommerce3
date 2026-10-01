<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\OrderPayment;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Support\Accounting\AccountingAvailability;
use App\Support\Accounting\ManualPostingResult;

/**
 * The gateway used whenever Advanced Accounting is off — whether because the
 * operator switched it off or the optional package is not installed at all.
 *
 * Every method is intentionally inert. This is what keeps the non-negotiable
 * promise true: with Advanced Accounting disabled the commerce path calls into
 * an object that does nothing, holds no reference to any package class, and
 * cannot fail. The Lite fund ledger, expenses, income, capital, withdrawals,
 * stock, COGS and reports are completely unaffected — no package service is
 * even constructed.
 */
final class NullAdvancedAccountingGateway implements AdvancedAccountingGateway
{
    public function available(): bool
    {
        // Honest answer about the package, even though this gateway means it
        // is not in use. Delegates to the single centralised detector.
        return AccountingAvailability::available();
    }

    public function enabled(): bool
    {
        // A null gateway is bound precisely because enabled() was false.
        return false;
    }

    public function readyForLivePosting(): bool
    {
        return false;
    }

    public function recordExpense(Expense $expense): ?ManualPostingResult
    {
        return null;
    }

    public function recordMoneyIn(FundTransaction $tx, string $nature): ?ManualPostingResult
    {
        return null;
    }

    public function recordWithdrawal(FundTransaction $tx): ?ManualPostingResult
    {
        return null;
    }

    public function expenseEditBlockReason(Expense $expense): ?string
    {
        return null;
    }

    public function expenseHasJournal(Expense $expense): bool
    {
        return false;
    }

    public function expenseNeedsEntry(Expense $expense): bool
    {
        return false;
    }

    public function fundEditBlockReason(FundTransaction $tx): ?string
    {
        return null;
    }

    public function fundHasJournal(FundTransaction $tx): bool
    {
        return false;
    }

    public function fundNeedsEntry(FundTransaction $tx): bool
    {
        return false;
    }

    public function fundNature(FundTransaction $tx): ?string
    {
        return null;
    }

    public function fundNaturesFor(array $sourceIds): array
    {
        return [];
    }

    public function paymentReceived(OrderPayment $payment): ?ManualPostingResult
    {
    }

    public function recordSale(Order $order): ?ManualPostingResult { return null; }

    public function recordCogs(Order $order): ?ManualPostingResult { return null; }

    public function recordPurchase(Purchase $purchase): ?ManualPostingResult { return null; }

    public function supplierPaymentMade(SupplierPayment $payment): ?ManualPostingResult { return null; }
}
