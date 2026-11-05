<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Accounting\AdvancedAccountingGateway;

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
    /**
     * The one way an order's status changes from outside the order screen (courier webhooks, courier polling). The
     * order row is locked and re-read, so the change is made from the status as it is now. The status change and its
     * stock, serial and ledger effects run in one transaction, and a change the order's current status does not allow
     * is refused, never forced. The sales reset locks the same row, so the two cannot interleave.
     *
     * @param  ?callable(Order): void  $afterChange  runs inside the same transaction, only when the change is applied
     * @return 'applied'|'unchanged'|'refused'
     */
    public function changeStatusLocked(int $orderId, OrderStatus $target, string $note, ?int $userId = null, ?callable $afterChange = null): string
    {
        return DB::transaction(function () use ($orderId, $target, $note, $userId, $afterChange) {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            $from = $order->status_enum;

            if ($from === $target) {
                return 'unchanged';
            }

            if (! $order->transitionTo($target, $note, $userId)) {
                return 'refused';
            }

            $this->handleStatusChange($order->fresh(), $from->value, $target->value);

            if ($afterChange !== null) {
                $afterChange($order->fresh());
            }

            return 'applied';
        });
    }

    /**
     * @param  bool  $strictCost  true for an administrative reset: the restock is refused unless every line has a
     *                            recorded batch allocation, and a sale already restocked is never restocked again.
     *                            Normal flows keep their existing behaviour (see the fallback in the restock below).
     */
    public function handleStatusChange(Order $order, $oldStatus, $newStatus, bool $strictCost = false): void
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

        // 1) Entering active status → decrease stock (with batch tracking).
        // One transaction: if any line cannot be allocated, every stock, serial and ledger change made here is undone.
        $authoritativeCogs = true;
        if ($isActive && !$wasActive) {
            DB::transaction(function () use ($order, $stockService, &$authoritativeCogs) {
                $details = OrderDetails::where('order_id', $order->id)
                    ->with('product', 'warrantySale')
                    ->get();

                foreach ($details as $row) {
                    if (!$row->product) {
                        continue;
                    }

                    // Serial-tracked lines are allocated strictly to their origin batches; a failure is never silent.
                    if ($row->warrantySale && is_array($row->warrantySale->serial_numbers) && $row->warrantySale->serial_numbers !== []) {
                        $allocated = app(\App\Services\Stock\SaleSerialAllocator::class)->allocate($order, $row, $row->warrantySale, $stockService);
                        $row->update([
                            'cogs'      => $allocated['cogs'],
                            'batch_ids' => $allocated['batch_details'],
                        ]);

                        continue;
                    }

                    try {
                        $result = $stockService->stockOut($row->product, (int) $row->qty, [
                            'type' => 'sale',
                            'id'   => $order->id,
                        ], optional($row->warrantySale)->stock_batch_id);

                        // Store COGS and batch details on the order detail
                        $row->update([
                            'cogs'      => $result['cogs'],
                            'batch_ids' => $result['batch_details'],
                        ]);

                        // POS and web sales create the warranty sale without a batch. Link it to the batch the stock was drawn from.
                        $this->linkWarrantySaleToBatch($row, $result['batch_details']);
                    } catch (\RuntimeException $e) {
                        $authoritativeCogs = false;
                        // Fallback: simple stock decrement if batch tracking fails (legacy, non-serial lines only)
                        $row->product->decrement('stock', (int) $row->qty);
                        Log::warning('Stock batch deduction failed, used fallback', [
                            'product' => $row->product_id,
                            'order'   => $order->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }

                $ledger = app(AdvancedAccountingGateway::class);
                $ledger->recordSale($order);
                if ($authoritativeCogs && $details->contains(fn ($row) => $row->cogs !== null && (float) $row->cogs > 0)) {
                    $ledger->recordCogs($order->fresh());
                }
            });
        }

        // 2) Cancelled / Returned → restore stock if was active
        if ($wasActive && in_array($newEnum, [OrderStatus::CANCELLED, OrderStatus::RETURNED], true)) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product', 'warrantySale')
                ->get();

            // Checked before anything changes: a sale is restocked once, and only from its recorded batches.
            // A reset also places every sold serial back in the batch it came out of.
            $serialOrigins = $strictCost ? $this->assertRestockable($order, $details) : [];

            // Ledger first: the restock below clears each line's COGS, and a sale carried by
            // the opening reverses its cost from that figure.
            app(AdvancedAccountingGateway::class)->reverseSale(
                $order,
                'Sale '.$newEnum->value.': '.($order->note ?? 'operational reversal'),
                $order->updated_by ?? auth()->id()
            );

            foreach ($details as $row) {
                if (!$row->product) {
                    continue;
                }

                // Restore stock at the cost each part was sold at, batch by batch (never the product's current price).
                $allocation = $row->stockAllocation();
                if ($allocation === null || array_sum(array_column($allocation, 'qty')) !== (int) $row->qty) {
                    // Only reachable outside a reset: the sale has no usable batch record. The old behaviour is kept
                    // so courier and return flows still complete, and the gap is logged for review.
                    Log::warning('Restock without a recorded batch allocation', ['order_id' => $order->id, 'line' => $row->id]);
                    $stockService->stockIn($row->product, [
                        'quantity'         => (int) $row->qty,
                        'unit_cost'        => (float) ($row->product->purchase_price ?? 0),
                        'reference_type'   => 'sale_return',
                        'reference_id'     => $order->id,
                        'variant_price_id' => $row->variant_price_id,
                    ]);
                    $allocation = [];
                }
                foreach ($allocation as $part) {
                    $stockService->stockIn($row->product, [
                        'quantity'         => $part['qty'],
                        'unit_cost'        => $part['unit_cost'],
                        'reference_type'   => 'sale_return',
                        'reference_id'     => $order->id,
                        'variant_price_id' => $row->variant_price_id,
                    ]);
                }

                // Phase 5.1 — return this line's sold serials to their original batch
                if ($strictCost) {
                    $this->restoreSerialsToOrigin($serialOrigins[$row->id] ?? [], $stockService);
                } else {
                    $this->restoreRowSerialsFromSold($row, $stockService);
                }

                // Clear COGS since it's reversed
                $row->update([
                    'cogs'       => null,
                    'batch_ids'  => null,
                ]);
            }

            // VAT: the sale is reversed, so its declared VAT is reversed by a credit note.
            app(\App\Services\Vat\VatCreditNoteService::class)->forOrder(
                $order,
                'Sale '.$newEnum->value.': '.($order->invoice_id ?: 'Order #'.$order->id),
                $order->updated_by ?? auth()->id()
            );
        }
    }

    /**
     * Refuses a restock that would be wrong: the sale was already restocked, a line has no recorded batch
     * allocation that adds up to the quantity sold, or a sold serial cannot be placed in its batch with certainty.
     * Throws before any stock or ledger change is made.
     *
     * @param  \Illuminate\Support\Collection<int, OrderDetails>  $details
     * @return array<int, array<string, int>>  order line id => serial => batch id
     */
    private function assertRestockable(Order $order, $details): array
    {
        if (\App\Models\StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->exists()) {
            throw new \DomainException('Stock was already restored for this sale.');
        }

        $origins = [];
        foreach ($details as $row) {
            if (!$row->product || (int) $row->qty <= 0) {
                continue;
            }

            $allocation = $row->stockAllocation();
            if ($allocation === null) {
                throw new \DomainException("\"{$row->product_name}\": the stock batches of this sale are not recorded, so it cannot be restocked at its original cost.");
            }
            if (array_sum(array_column($allocation, 'qty')) !== (int) $row->qty) {
                throw new \DomainException("\"{$row->product_name}\": the recorded batches do not add up to the quantity sold.");
            }

            $serials = is_array(optional($row->warrantySale)->serial_numbers) ? $row->warrantySale->serial_numbers : [];
            if ($serials !== []) {
                $origins[$row->id] = app(\App\Services\TransactionReset\SerialOrigin::class)->resolve(
                    (int) $row->product_id,
                    $serials,
                    $row->warrantySale->stock_batch_id !== null ? (int) $row->warrantySale->stock_batch_id : null,
                );
            }
        }

        return $origins;
    }

    /** Puts each serial back into the batch it was sold from (strict reset path only). */
    private function restoreSerialsToOrigin(array $map, StockManagementService $stockService): void
    {
        $byBatch = [];
        foreach ($map as $serial => $batchId) {
            $byBatch[$batchId][] = $serial;
        }

        foreach ($byBatch as $batchId => $serials) {
            $batch = \App\Models\StockBatch::query()->lockForUpdate()->findOrFail($batchId);
            $stockService->restoreSerialsFromSold($batch, $serials);
        }
    }

    /**
     * Sets the warranty sale's batch from the first batch the line drew stock from,
     * when it has none. A line split across batches is linked to its first batch.
     */
    private function linkWarrantySaleToBatch(OrderDetails $row, array $batchDetails): void
    {
        $ws = $row->warrantySale;
        if (!$ws || $ws->stock_batch_id || empty($batchDetails)) {
            return;
        }

        $batchId = $batchDetails[0]['batch_id'] ?? null;
        if ($batchId) {
            $ws->update(['stock_batch_id' => (int) $batchId]);
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
