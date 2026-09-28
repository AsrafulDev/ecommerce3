<?php

namespace App\Services\Accounting;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Refund;
use App\Models\SupplierPayment;

/**
 * The gateway used whenever full accounting is off.
 *
 * Every method is intentionally empty. This is what keeps the non-negotiable
 * promise true: with full accounting disabled — or the package not installed at
 * all — the commerce path calls into an object that does nothing, holds no
 * reference to any package class, and cannot fail. The Lite fund ledger and the
 * order lifecycle are completely unaffected.
 */
final class NullFullAccountingGateway implements FullAccountingGateway
{
    public function paymentReceived(OrderPayment $payment): void
    {
    }

    public function saleDelivered(Order $order): void
    {
    }

    public function purchaseReceived(Purchase $purchase): void
    {
    }

    public function supplierPaymentPosted(SupplierPayment $payment): void
    {
    }

    public function refundProcessed(Refund $refund): void
    {
    }

    public function orderRefunded(Order $order): void
    {
    }

    public function saleReversed(Order $order, string $reason): void
    {
    }
}
