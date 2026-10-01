<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\OrderPayment;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Support\Accounting\ManualPostingResult;

/**
 * The seam between commerce and Advanced Accounting (the optional double-entry
 * ledger). Host code (controllers, services, listeners) depends on this
 * interface and knows nothing about journals, accounts or the
 * Softmit\DoubleEntry package.
 *
 * Two implementations exist and the container picks one via
 * App\Support\Accounting\AccountingAvailability:
 *
 *   NullAdvancedAccountingGateway        — every operation inert; used when
 *                                          Advanced Accounting is disabled or
 *                                          the package is not installed.
 *   DoubleEntryAdvancedAccountingGateway — the real adapter; package imports
 *                                          live there (and in the services it
 *                                          delegates to), never in commerce
 *                                          controllers.
 *
 * Rules every implementation must honour:
 *  - No method may let an exception escape into a commerce flow. Money moves
 *    in the Lite ledger first; a failed posting is recorded and reported to
 *    the operator, never silently swallowed (see ManualPostingResult::FAILED).
 *  - Disabled is not the same as failed: with Advanced off, Null is the
 *    expected, correct answer — Lite rows are simply not journalled.
 *  - Methods are added only when a real transaction integration needs them.
 *    Commerce-event posting (sale, purchase, returns) joins as those
 *    integrations land; they are deliberately not declared while unimplemented.
 */
interface AdvancedAccountingGateway
{
    /** Is the optional double-entry package installed? */
    public function available(): bool;

    /** Available AND switched on — Advanced Accounting actually posts. */
    public function enabled(): bool;

    /** True only when Advanced is enabled and the opening journal is POSTED. */
    public function readyForLivePosting(): bool;

    /*
    |--------------------------------------------------------------------------
    | Manual money screens (the first real integration: expense, income,
    | capital, withdrawal)
    |--------------------------------------------------------------------------
    | The Lite screens store their row first, then ask the books for the
    | double entry. Null result means "the books were not asked" (disabled);
    | ManualPostingResult::FAILED means they were asked and refused — the
    | operator must see the difference.
    */

    /** Dr expense account / Cr cash — the journal for a recorded expense. */
    public function recordExpense(Expense $expense): ?ManualPostingResult;

    /**
     * Dr cash / Cr owner capital OR other income — money in, and the nature
     * says whether it is equity (never profit) or genuine income.
     */
    public function recordMoneyIn(FundTransaction $tx, string $nature): ?ManualPostingResult;

    /** Dr owner drawings / Cr cash — money out to the owner is not an expense. */
    public function recordWithdrawal(FundTransaction $tx): ?ManualPostingResult;

    /**
     * Why the expense row may not be edited/deleted right now, or null when
     * the books have no standing claim on it.
     */
    public function expenseEditBlockReason(Expense $expense): ?string;

    /** Any journal at all (even a reversed one) points at this expense. */
    public function expenseHasJournal(Expense $expense): bool;

    /** The expense is owed a fresh entry (never journalled, or reversed). */
    public function expenseNeedsEntry(Expense $expense): bool;

    /** Why the fund row may not be edited/deleted right now, or null. */
    public function fundEditBlockReason(FundTransaction $tx): ?string;

    /** Any journal at all (even a reversed one) points at this fund row. */
    public function fundHasJournal(FundTransaction $tx): bool;

    /** The fund row is owed a fresh entry (never journalled, or reversed). */
    public function fundNeedsEntry(FundTransaction $tx): bool;

    /** Which nature a money-in row was booked as, read back from its journal. */
    public function fundNature(FundTransaction $tx): ?string;

    /**
     * Nature for a whole page of money-in rows, one lookup.
     *
     * @param  array<int, int>  $sourceIds  fund transaction ids
     * @return array<int, string> fund_transaction_id => owner_capital|other_income
     */
    public function fundNaturesFor(array $sourceIds): array;

    /*
    |--------------------------------------------------------------------------
    | Commerce events
    |--------------------------------------------------------------------------
    */

    /**
     * A payment was collected against an order (cash side: Dr Cash / Cr AR).
     * Wired since the gateway seam landed; the accrual posting itself joins
     * with the customer-payment integration phase.
     */
    public function paymentReceived(OrderPayment $payment): ?ManualPostingResult;

    public function recordSale(Order $order): ?ManualPostingResult;

    public function recordCogs(Order $order): ?ManualPostingResult;

    public function recordPurchase(Purchase $purchase): ?ManualPostingResult;

    public function supplierPaymentMade(SupplierPayment $payment): ?ManualPostingResult;
}
