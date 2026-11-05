<?php

namespace App\Services\TransactionReset;

use App\Models\StockBatch;

/**
 * Finds the batch each sold serial number came out of, from the batches' own records (sn_sold). A serial is
 * never assigned to a batch by guesswork: it must be recorded as sold in exactly one batch of the product, must not
 * be listed as in stock anywhere, and must agree with the batch the sale is linked to, when one is linked.
 */
final class SerialOrigin
{
    /**
     * @param  list<string>  $serials
     * @return array<string, int>  serial => batch id it returns to
     *
     * @throws \DomainException  when any serial cannot be placed with certainty
     */
    public function resolve(int $productId, array $serials, ?int $linkedBatchId): array
    {
        $batches = StockBatch::query()
            ->where('product_id', $productId)
            ->get(['id', 'sn_stock', 'sn_sold']);

        $map = [];
        foreach (array_values(array_unique(array_filter(array_map('trim', $serials)))) as $serial) {
            $soldIn = $batches->filter(fn ($batch) => in_array($serial, (array) $batch->sn_sold, true))->pluck('id')->values();
            $inStock = $batches->filter(fn ($batch) => in_array($serial, (array) $batch->sn_stock, true))->count();

            if ($soldIn->isEmpty()) {
                throw new \DomainException("Serial {$serial} is not recorded as sold from any batch, so it cannot be returned to its batch.");
            }
            if ($soldIn->count() > 1) {
                throw new \DomainException("Serial {$serial} is recorded as sold from more than one batch.");
            }
            if ($inStock > 0) {
                throw new \DomainException("Serial {$serial} is already listed as in stock, so returning it would list it twice.");
            }

            $batchId = (int) $soldIn->first();
            if ($linkedBatchId !== null && $linkedBatchId !== $batchId) {
                throw new \DomainException("Serial {$serial} came from batch {$batchId}, but the sale is linked to batch {$linkedBatchId}.");
            }

            $map[$serial] = $batchId;
        }

        return $map;
    }
}
