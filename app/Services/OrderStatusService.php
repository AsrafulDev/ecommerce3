<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ SINGLE shared order-status → stock engine (UPDATE-PLAN 3.2).
 *
 * Every place that changes an order's status between the "active" (stock-holding)
 * group and CANCELLED / RETURNED routes its stock side-effects through here:
 *   - entering an active status  → batch-tracked stockOut (+ COGS on the detail)
 *   - leaving for CANCELLED/RETURNED → restock via stockIn (reference_type sale_return)
 *
 * Old/new may be legacy ints or OrderStatus enum values. This replaced the
 * private copy in OrderController and the webhook's direct-write engine.
 */
class OrderStatusService
{
    public function handleStatusChange(Order $order, $oldStatus, $newStatus): void
    {
        $oldEnum = is_int($oldStatus) ? OrderStatus::fromLegacyId($oldStatus) : OrderStatus::tryFrom($oldStatus);
        $newEnum = is_int($newStatus) ? OrderStatus::fromLegacyId($newStatus) : OrderStatus::tryFrom($newStatus);

        if (!$oldEnum || !$newEnum) {
            return;
        }

        $wasActive = $oldEnum->consumesStock();
        $isActive  = $newEnum->consumesStock();

        /** @var StockManagementService $stockService */
        $stockService = app(StockManagementService::class);

        // 1) Entering active status → decrease stock (with batch tracking)
        if ($isActive && !$wasActive) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product', 'warrantySale')
                ->get();

            foreach ($details as $row) {
                if (!$row->product) {
                    continue;
                }

                try {
                    // ✅ Pass user-selected batch from WarrantySale if available
                    $preferredBatchId = optional($row->warrantySale)->stock_batch_id;
                    $result = $stockService->stockOut($row->product, (int) $row->qty, [
                        'type' => 'sale',
                        'id'   => $order->id,
                    ], $preferredBatchId);

                    // Store COGS and batch details on the order detail
                    $row->update([
                        'cogs'      => $result['cogs'],
                        'batch_ids' => $result['batch_details'],
                    ]);

                    // Phase 5.1 — move this line's sold serials sn_stock → sn_sold
                    $this->moveRowSerialsToSold($row);
                } catch (\RuntimeException $e) {
                    // Fallback: simple stock decrement if batch tracking fails
                    $row->product->decrement('stock', (int) $row->qty);
                    Log::warning('Stock batch deduction failed, used fallback', [
                        'product' => $row->product_id,
                        'order'   => $order->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }

        // 2) Cancelled / Returned → restore stock if was active
        if ($wasActive && in_array($newEnum, [OrderStatus::CANCELLED, OrderStatus::RETURNED], true)) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product', 'warrantySale')
                ->get();

            foreach ($details as $row) {
                if (!$row->product) {
                    continue;
                }

                // Restore stock — create a positive batch entry
                $stockService->stockIn($row->product, [
                    'quantity'       => (int) $row->qty,
                    'unit_cost'      => (float) ($row->product->purchase_price ?? 0),
                    'reference_type' => 'sale_return',
                    'reference_id'   => $order->id,
                ]);

                // Phase 5.1 — return this line's sold serials to their original batch
                $this->restoreRowSerialsFromSold($row, $stockService);

                // Clear COGS since it's reversed
                $row->update([
                    'cogs'       => null,
                    'batch_ids'  => null,
                ]);
            }
        }
    }

    /**
     * Phase 5.1 — move a sold detail's serials from the batch sn_stock → sn_sold.
     */
    private function moveRowSerialsToSold(OrderDetails $row): void
    {
        $ws = $row->warrantySale;
        if (!$ws) {
            return;
        }
        $serials = is_array($ws->serial_numbers ?? null) ? $ws->serial_numbers : [];
        $batchId = $ws->stock_batch_id ?? null;
        if (!$serials || !$batchId) {
            return;
        }

        $batch = \App\Models\StockBatch::find($batchId);
        if ($batch) {
            app(StockManagementService::class)->moveSerialsToSold($batch, $serials);
        }
    }

    /**
     * Phase 5.1 — on cancel/return, move a sold detail's serials back sn_sold → sn_stock.
     */
    private function restoreRowSerialsFromSold(OrderDetails $row, StockManagementService $stockService): void
    {
        $ws = $row->warrantySale;
        if (!$ws) {
            return;
        }
        $serials = is_array($ws->serial_numbers ?? null) ? $ws->serial_numbers : [];
        $batchId = $ws->stock_batch_id ?? null;
        if (!$serials || !$batchId) {
            return;
        }

        $batch = \App\Models\StockBatch::find($batchId);
        if ($batch) {
            $stockService->restoreSerialsFromSold($batch, $serials);
        }
    }
}
