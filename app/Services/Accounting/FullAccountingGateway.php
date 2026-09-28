<?php

namespace App\Services\Accounting;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Refund;
use App\Models\SupplierPayment;

/**
 * The seam between commerce and LEVEL 2 (the double-entry ledger).
 *
 * Host code (controllers, services, listeners) calls these methods and knows
 * nothing about journals, accounts or the Softmit\DoubleEntry package. Two
 * implementations exist:
 *
 *   NullFullAccountingGateway  — every method empty; used when full accounting
 *                                is disabled or the package is not installed.
 *   CommerceLedgerService      — the real accrual bridge; never throws.
 *
 * The contract is explicit about what implementations must guarantee: no method
 * here may ever let an exception escape into a commerce flow. Money moves in the
 * Lite ledger and the order lifecycle come first; a failed or skipped posting is
 * a reporting problem, not a reason to abort placing or paying an order.
 */
interface FullAccountingGateway
{
    /**
     * A payment was collected against an order (cash side): Dr Cash / Cr AR.
     */
    public function paymentReceived(OrderPayment $payment): void;

    /**
     * An order reached delivered/completed (revenue side): the revenue journal
     * and its matching COGS journal.
     */
    public function saleDelivered(Order $order): void;

    /**
     * A purchase was received: Dr Inventory / Cr Accounts Payable.
     */
    public function purchaseReceived(Purchase $purchase): void;

    /**
     * A supplier payment was recorded: Dr Accounts Payable / Cr Cash.
     */
    public function supplierPaymentPosted(SupplierPayment $payment): void;

    /**
     * A refund was processed for a returned order (contra revenue + restock).
     */
    public function refundProcessed(Refund $refund): void;

    /**
     * A refund raised straight from an order's payment-status toggle.
     */
    public function orderRefunded(Order $order): void;

    /**
     * A sale was cancelled before delivery: reverse the revenue and COGS
     * journals rather than editing them.
     */
    public function saleReversed(Order $order, string $reason): void;
}
