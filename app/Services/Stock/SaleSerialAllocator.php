<?php

namespace App\Services\Stock;

use App\Models\OrderDetails;
use App\Models\Order;
use App\Models\StockBatch;
use App\Models\WarrantySale;
use App\Services\StockManagementService;

/**
 * Allocates a serial-tracked sale line to the batches its serial numbers actually came from.
 *
 * Every serial must be listed as in stock in exactly one batch of the product, must not already be recorded as sold,
 * and must agree with the batch the sale is linked to when one is linked. Stock is taken from those batches, and each
 * serial is moved to sold in its own batch. Anything unclear throws, and the caller's transaction rolls back, so no
 * serial is skipped, duplicated or moved to the wrong batch. Nothing here invents an allocation or changes history.
 */
final class SaleSerialAllocator
{
    /**
     * @return array{cogs: float, batch_details: list<array<string, mixed>>}
     *
     * @throws \DomainException  when the serials cannot be allocated with certainty
     */
    public function allocate(Order $order, OrderDetails $line, WarrantySale $warranty, StockManagementService $stock): array
    {
        $serials = $this->normalised($warranty->serial_numbers);
        $qty = (int) $line->qty;

        if (count($serials) !== $qty) {
            throw new \DomainException("\"{$line->product_name}\": {$qty} sold but ".count($serials).' serial number(s) recorded.');
        }

        // Lock every batch of the product, so no other sale or stock change can take these serials meanwhile.
        $batches = StockBatch::query()
            ->where('product_id', $line->product_id)
            ->lockForUpdate()
            ->get(['id', 'sn_stock', 'sn_sold', 'remaining_qty', 'product_id']);

        $origins = [];
        foreach ($serials as $serial) {
            $inStock = $batches->filter(fn ($batch) => in_array($serial, (array) $batch->sn_stock, true));
            $sold = $batches->filter(fn ($batch) => in_array($serial, (array) $batch->sn_sold, true));

            if ($inStock->isEmpty()) {
                throw $sold->isNotEmpty()
                    ? new \DomainException("Serial {$serial} is already sold.")
                    : new \DomainException("Serial {$serial} is not in stock for \"{$line->product_name}\".");
            }
            if ($inStock->count() > 1) {
                throw new \DomainException("Serial {$serial} is listed in more than one batch.");
            }
            if ($sold->isNotEmpty()) {
                throw new \DomainException("Serial {$serial} is both in stock and recorded as sold.");
            }

            $batchId = (int) $inStock->first()->id;
            if ($warranty->stock_batch_id !== null && (int) $warranty->stock_batch_id !== $batchId) {
                throw new \DomainException("Serial {$serial} is in batch {$batchId}, but the sale is linked to batch {$warranty->stock_batch_id}.");
            }

            $origins[$batchId][] = $serial;
        }

        // Every batch must hold enough stock for its serials before anything is taken.
        foreach ($origins as $batchId => $list) {
            $remaining = (int) $batches->firstWhere('id', $batchId)->remaining_qty;
            if ($remaining < count($list)) {
                throw new \DomainException("Batch {$batchId} has {$remaining} unit(s) left, but {$line->product_name} needs ".count($list).' from it.');
            }
        }

        $cogs = 0.0;
        $details = [];
        $product = $line->product;
        foreach ($origins as $batchId => $list) {
            // Takes exactly these units from this batch: the preferred batch holds enough, so no other batch is drawn on.
            $result = $stock->stockOut($product, count($list), ['type' => 'sale', 'id' => $order->id], (int) $batchId);
            $cogs += (float) $result['cogs'];
            array_push($details, ...$result['batch_details']);

            $batch = StockBatch::query()->lockForUpdate()->findOrFail($batchId);
            $stock->moveSerialsToSold($batch, $list);

            $fresh = $batch->fresh();
            foreach ($list as $serial) {
                if (! in_array($serial, (array) $fresh->sn_sold, true) || in_array($serial, (array) $fresh->sn_stock, true)) {
                    throw new \DomainException("Serial {$serial} could not be moved to sold in batch {$batchId}.");
                }
            }
        }

        if ($warranty->stock_batch_id === null) {
            $warranty->update(['stock_batch_id' => (int) array_key_first($origins)]);
        }

        return ['cogs' => round($cogs, 2), 'batch_details' => $details];
    }

    /** @return list<string> */
    private function normalised(mixed $serials): array
    {
        $list = array_values(array_filter(array_map(fn ($s) => trim((string) $s), (array) $serials), fn ($s) => $s !== ''));

        $duplicates = array_keys(array_filter(array_count_values($list), fn ($count) => $count > 1));
        if ($duplicates !== []) {
            throw new \DomainException('Serial '.$duplicates[0].' is listed more than once on this sale.');
        }

        return $list;
    }
}
