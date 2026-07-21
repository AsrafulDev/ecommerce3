<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\WarrantyService;

class OrderWarrantyObserver
{
    public function __construct(
        private WarrantyService $warrantyService,
    ) {}

    /**
     * When order status changes to DELIVERED, activate warranty countdown.
     */
    public function updated(Order $order): void
    {
        if ($order->wasChanged('order_status')) {
            $newStatus = $order->order_status;

            if ($newStatus === OrderStatus::DELIVERED->value) {
                $this->warrantyService->activateOnDelivery($order);
            }

            // If order is returned, void warranties
            if ($newStatus === OrderStatus::RETURNED->value) {
                $warranties = $order->warrantySales;
                foreach ($warranties as $sale) {
                    $this->warrantyService->voidWarranty($sale);
                }
            }

            // If order is cancelled, void warranties
            if ($newStatus === OrderStatus::CANCELLED->value) {
                $warranties = $order->warrantySales;
                foreach ($warranties as $sale) {
                    $this->warrantyService->voidWarranty($sale);
                }
            }
        }
    }
}
