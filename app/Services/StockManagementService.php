<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\StockAdjustment;
use App\Models\GeneralSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockManagementService
{
    /**
     * Resolve which costing method to use for a product.
     * Precedence: purchase > product > global (GeneralSetting)
     */
    public function resolveMethod(?Product $product, ?Purchase $purchase = null): string
    {
        // 1) Purchase-level override
        if ($purchase && $purchase->costing_method) {
            return $purchase->costing_method;
        }

        // 2) Product-level override
        if ($product && $product->costing_method) {
            return $product->costing_method;
        }

        // 3) Global default
        $global = GeneralSetting::first();
        return $global->default_costing_method ?? 'fifo';
    }

    /**
     * Record stock coming in (purchase, return, adjustment).
     *
     * @param Product $product
     * @param array $data  Keys: quantity, unit_cost, selling_price?, batch_no?, mfg_date?, exp_date?,
     *                     supplier_id?, purchase_id?, variant_price_id?, reference_type?, reference_id?
     * @return StockBatch
     */
    public function stockIn(Product $product, array $data): StockBatch
    {
        $qty = (int) ($data['quantity'] ?? 0);

        if ($qty <= 0) {
            throw new \InvalidArgumentException('Stock-in quantity must be positive.');
        }

        // Create the batch record
        $batch = StockBatch::create([
            'product_id'      => $product->id,
            'variant_price_id' => $data['variant_price_id'] ?? null,
            'purchase_id'     => $data['purchase_id'] ?? null,
            'supplier_id'     => $data['supplier_id'] ?? null,
            'batch_no'        => $data['batch_no'] ?? null,
            'quantity'        => $qty,
            'remaining_qty'   => $qty,
            'unit_cost'       => $data['unit_cost'] ?? 0,
            'selling_price'   => $data['selling_price'] ?? null,
            'mrp'             => $data['mrp'] ?? null,
            'wholesale_price' => $data['wholesale_price'] ?? null,
            'is_active_for_website' => (bool) ($data['is_active_for_website'] ?? false),
            'pos_enabled'     => (bool) ($data['pos_enabled'] ?? true),
            'auto_advance'    => (bool) ($data['auto_advance'] ?? config('pricing.auto_advance_default', true)),
            'mfg_date'        => $data['mfg_date'] ?? null,
            'exp_date'        => $data['exp_date'] ?? null,
            'custom_field'    => $data['custom_field'] ?? null,
            'type'            => 'in',
            'reference_type'  => $data['reference_type'] ?? 'purchase',
            'reference_id'    => $data['reference_id'] ?? null,
            'reference_no'    => $this->resolveReferenceNo([
                'type' => $data['reference_type'] ?? null,
                'id'   => $data['reference_id'] ?? null,
            ]),
        ]);

        // Store per-unit serial numbers (SN) if provided for this stock-in
        if (!empty($data['serial_numbers']) && is_array($data['serial_numbers'])) {
            $serials = array_values(array_filter(array_map(
                fn ($s) => trim((string) $s),
                $data['serial_numbers']
            )));
            if ($serials) {
                $batch->sn_stock = $serials;
                $batch->save();
            }
        }

        // Update product stock count
        $product->increment('stock', $qty);

        // If costing method is 'average', recalculate purchase_price
        $method = $this->resolveMethod($product);
        if ($method === 'average') {
            $this->recalculateAverageCost($product, $data['unit_cost'] ?? 0, $qty);
        }

        // Update variant stock if applicable
        if (!empty($data['variant_price_id'])) {
            ProductVariantPrice::where('id', $data['variant_price_id'])
                ->increment('stock', $qty);
        }

        return $batch;
    }

    /**
     * Record stock going out (sale, return to supplier, adjustment).
     * Uses LIFO/FIFO/Average to determine COGS and which batches to deduct from.
     *
     * @param Product $product
     * @param int $qty
     * @param array $reference  Keys: type (sale, purchase_return, adjustment), id
     * @param int|null $preferredBatchId  Optional: deduct from this specific batch first
     * @return array  ['cogs' => float, 'batch_details' => [...], 'remaining' => int]
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function stockOut(Product $product, int $qty, array $reference = [], ?int $preferredBatchId = null): array
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Stock-out quantity must be positive.');
        }

        // Check stock availability
        $availableStock = $product->stock ?? 0;
        $allowNegative = (bool) ($product->allow_negative_stock ?? false);

        if ($qty > $availableStock && !$allowNegative) {
            throw new \RuntimeException(
                "Insufficient stock for product '{$product->name}'. " .
                "Requested: {$qty}, Available: {$availableStock}"
            );
        }

        $method = $this->resolveMethod($product);
        $totalCogs = 0;
        $batchDetails = [];
        $remaining = $qty;

        // ✅ Try user-selected batch first (if specified and has stock)
        if ($preferredBatchId && $remaining > 0) {
            $preferredBatch = StockBatch::where('id', $preferredBatchId)
                ->where('product_id', $product->id)
                ->where('remaining_qty', '>', 0)
                ->first();

            if ($preferredBatch) {
                $deduct = min($remaining, $preferredBatch->remaining_qty);
                $cogs = $deduct * (float) $preferredBatch->unit_cost;
                $preferredBatch->decrement('remaining_qty', $deduct);
                $batchDetails[] = [
                    'batch_id'  => $preferredBatch->id,
                    'qty'       => $deduct,
                    'unit_cost' => (float) $preferredBatch->unit_cost,
                    'cogs'      => $cogs,
                ];
                $totalCogs += $cogs;
                $remaining -= $deduct;
            }
        }

        // ✅ Remaining qty: use costing method (FIFO/LIFO/Average)
        if ($remaining > 0) {
        if ($method === 'average') {
            // Average Cost: COGS = current purchase_price * qty
            $avgCost = (float) ($product->purchase_price ?? 0);
            $totalCogs += $avgCost * $remaining;

            // Deduct proportionally from all batches (simplified: FIFO order but with avg cost)
            // Auto-selection: batch qty > 0, warranty lowest value (> 0) first priority
            $batches = $this->getAutoSelectionBatches($product, 'fifo');
            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }
                $deduct = min($remaining, $batch->remaining_qty);
                $batch->decrement('remaining_qty', $deduct);
                $batchDetails[] = [
                    'batch_id'  => $batch->id,
                    'qty'       => $deduct,
                    'unit_cost' => $avgCost,
                    'cogs'      => $avgCost * $deduct,
                ];
                $remaining -= $deduct;
            }
        } else {
            // FIFO or LIFO
            $order = ($method === 'fifo') ? 'asc' : 'desc';

            // Auto-selection: batch qty > 0, warranty lowest value (> 0) first priority
            $batches = $this->getAutoSelectionBatches($product, $method);
            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($remaining, $batch->remaining_qty);
                $cogs = $deduct * (float) $batch->unit_cost;

                $batch->decrement('remaining_qty', $deduct);
                $batchDetails[] = [
                    'batch_id'  => $batch->id,
                    'qty'       => $deduct,
                    'unit_cost' => (float) $batch->unit_cost,
                    'cogs'      => $cogs,
                ];
                $totalCogs += $cogs;
                $remaining -= $deduct;
            }
        }
        } // end if ($remaining > 0)

        if ($remaining > 0 && !$allowNegative) {
            throw new \RuntimeException("Insufficient stock in batches for product: {$product->name}");
        }

        // If negative stock is allowed and we still have remaining, use current avg cost
        if ($remaining > 0 && $allowNegative) {
            $avgCost = (float) ($product->purchase_price ?? 0);
            $cogs = $remaining * $avgCost;
            $totalCogs += $cogs;
            $batchDetails[] = [
                'batch_id'  => null,
                'qty'       => $remaining,
                'unit_cost' => $avgCost,
                'cogs'      => $cogs,
            ];
        }

        // Create outflow batch record for traceability.
        // Capture the source (primary) batch details + human-readable reference
        // so the Stock Batches page shows batch no / supplier / cost / order info.
        $sourceBatchId = null;
        foreach ($batchDetails as $bd) {
            if (!empty($bd['batch_id'])) {
                $sourceBatchId = $bd['batch_id'];
                break;
            }
        }
        $sourceBatch = $sourceBatchId ? StockBatch::find($sourceBatchId) : null;

        StockBatch::create([
            'product_id'       => $product->id,
            'variant_price_id' => $sourceBatch?->variant_price_id,
            'purchase_id'      => $sourceBatch?->purchase_id,
            'supplier_id'      => $sourceBatch?->supplier_id,
            'batch_no'         => $sourceBatch?->batch_no,
            'quantity'         => -$qty,
            'remaining_qty'    => 0,
            'unit_cost'        => $qty > 0 ? round($totalCogs / $qty, 2) : 0,
            'type'             => 'out',
            'reference_type'   => $reference['type'] ?? 'sale',
            'reference_id'     => $reference['id'] ?? null,
            'reference_no'     => $this->resolveReferenceNo($reference),
        ]);

        // Update product stock
        $product->decrement('stock', $qty);

        return [
            'cogs'          => $totalCogs,
            'batch_details' => $batchDetails,
            'remaining'     => $remaining,
        ];
    }

    /**
     * Resolve a human-readable reference (e.g. "Sale #26887", "Purchase #PUR-...")
     * from the reference array so batch records show the source document.
     */
    protected function resolveReferenceNo(array $reference): ?string
    {
        $type = $reference['type'] ?? null;
        $id   = $reference['id'] ?? null;
        if (!$type || !$id) {
            return null;
        }

        if ($type === 'sale') {
            $order = \App\Models\Order::find($id);
            return $order ? 'Sale #' . $order->invoice_id : null;
        }

        if ($type === 'purchase') {
            $purchase = \App\Models\Purchase::find($id);
            return $purchase ? 'Purchase #' . $purchase->invoice_no : null;
        }

        return null;
    }

    /**
     * Calculate COGS for a given quantity without actually deducting stock.
     */
    public function calculateCogs(Product $product, int $qty): float
    {
        $method = $this->resolveMethod($product);

        if ($method === 'average') {
            return (float) ($product->purchase_price ?? 0) * $qty;
        }

        $order = ($method === 'fifo') ? 'asc' : 'desc';
        $batches = StockBatch::where('product_id', $product->id)
            ->where('remaining_qty', '>', 0)
            ->where('type', 'in')
            ->orderBy('created_at', $order)
            ->get();

        $totalCogs = 0;
        $remaining = $qty;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = min($remaining, $batch->remaining_qty);
            $totalCogs += $deduct * (float) $batch->unit_cost;
            $remaining -= $deduct;
        }

        return $totalCogs;
    }

    /**
     * Get available (in-stock) batches for a product, ordered by the costing method.
     */
    public function getAvailableBatches(Product $product, ?string $method = null): Collection
    {
        $method = $method ?? $this->resolveMethod($product);
        $order = ($method === 'fifo') ? 'asc' : 'desc';

        return StockBatch::where('product_id', $product->id)
            ->where('remaining_qty', '>', 0)
            ->where('type', 'in')
            ->orderBy('created_at', $order)
            ->get();
    }

    /**
     * Get batches for AUTO-selection, ordered by the business priority:
     *   1) only available batches (remaining_qty > 0)
     *   2) batches with a valid warranty (remaining days > 0) come first
     *   3) among those, LOWEST warranty days first (sell soonest-expiring warranty first)
     *   4) otherwise fall back to the selling method order (FIFO asc / LIFO desc)
     */
    public function getAutoSelectionBatches(Product $product, ?string $method = null): Collection
    {
        $method = $method ?? $this->resolveMethod($product);
        $order  = ($method === 'fifo') ? 'asc' : 'desc';

        return StockBatch::where('product_id', $product->id)
            ->where('remaining_qty', '>', 0)
            ->where('type', 'in')
            ->orderBy('created_at', $order)
            ->get()
            ->each(function (StockBatch $batch) {
                $days = $batch->supplier_warranty_days ?? 0;
                $batch->setAttribute('auto_warranty_days', $days);
            })
            ->sortBy([
                // Has warranty (> 0) first, no-warranty batches last
                fn (StockBatch $b) => (int) $b->auto_warranty_days > 0 ? 0 : 1,
                // Lowest positive warranty days first
                fn (StockBatch $b) => (int) $b->auto_warranty_days,
            ])
            // Stable sort → equal keys keep the query order (FIFO asc / LIFO desc)
            ->values();
    }

    /**
     * Get the current total valuation of a product's stock.
     */
    public function getCurrentValuation(Product $product): float
    {
        $method = $this->resolveMethod($product);

        if ($method === 'average') {
            return (float) ($product->purchase_price ?? 0) * (int) ($product->stock ?? 0);
        }

        // LIFO/FIFO: sum of (remaining_qty * unit_cost) for all in-batches
        return (float) StockBatch::where('product_id', $product->id)
            ->where('remaining_qty', '>', 0)
            ->where('type', 'in')
            ->get()
            ->sum(fn($b) => $b->remaining_qty * (float) $b->unit_cost);
    }

    /**
     * Perform a manual stock adjustment.
     */
    public function adjustStock(
        Product $product,
        int $qty,
        string $type,    // 'addition', 'reduction', 'correction'
        string $reason,
        ?int $variantPriceId = null,
        ?float $sellingPrice = null,
        ?float $mrp = null
    ): StockAdjustment {
        $currentStock = (int) $product->stock;
        $newStock = $currentStock;

        if ($type === 'addition') {
            $newStock = $currentStock + $qty;
            // Treat as stock-in
            $this->stockIn($product, [
                'quantity'         => $qty,
                'unit_cost'        => (float) ($product->purchase_price ?? 0),
                'selling_price'    => $sellingPrice,
                'mrp'              => $mrp,
                'variant_price_id' => $variantPriceId,
                'reference_type'   => 'adjustment',
            ]);
        } elseif ($type === 'reduction') {
            $newStock = max(0, $currentStock - $qty);
            $actualReduce = $currentStock - $newStock;
            if ($actualReduce > 0) {
                try {
                    $this->stockOut($product, $actualReduce, ['type' => 'adjustment']);
                } catch (\RuntimeException $e) {
                    // If insufficient batches, just adjust the stock count directly
                    $product->decrement('stock', $actualReduce);
                }
            }
        } elseif ($type === 'correction') {
            // Set stock to exact qty
            $diff = $qty - $currentStock;
            if ($diff > 0) {
                $this->stockIn($product, [
                    'quantity'         => $diff,
                    'unit_cost'        => (float) ($product->purchase_price ?? 0),
                    'selling_price'    => $sellingPrice,
                    'mrp'              => $mrp,
                    'variant_price_id' => $variantPriceId,
                    'reference_type'   => 'adjustment',
                ]);
            } elseif ($diff < 0) {
                $actualReduce = abs($diff);
                try {
                    $this->stockOut($product, $actualReduce, ['type' => 'adjustment']);
                } catch (\RuntimeException $e) {
                    $product->decrement('stock', $actualReduce);
                }
            }
            $newStock = $qty;
        }

        $adjustment = StockAdjustment::create([
            'product_id'       => $product->id,
            'variant_price_id' => $variantPriceId,
            'type'             => $type,
            'quantity'         => $qty,
            'current_stock'    => $currentStock,
            'new_stock'        => $product->fresh()->stock ?? $newStock,
            'reason'           => $reason,
            'reference_type'   => 'adjustment',
            'created_by'       => Auth::id(),
        ]);

        return $adjustment;
    }

    /**
     * Recalculate the weighted average purchase price for a product.
     */
    private function recalculateAverageCost(Product $product, float $newUnitCost, int $newQty): void
    {
        $currentStock = (int) $product->stock;
        $currentAvgCost = (float) ($product->purchase_price ?? 0);

        // Subtract the just-added qty to get stock before this addition
        $stockBefore = $currentStock - $newQty;

        if ($stockBefore <= 0) {
            $newAvg = $newUnitCost;
        } else {
            $totalValueBefore = $currentAvgCost * $stockBefore;
            $newTotalValue = $totalValueBefore + ($newUnitCost * $newQty);
            $newAvg = $newTotalValue / $currentStock;
        }

        $product->update(['purchase_price' => round($newAvg, 2)]);
    }

    /**
     * Recalculate products.stock (and product_variant_prices.stock) from stock_batches.
     * This is the source-of-truth reconciliation for the denormalized stock column.
     *
     * @param int|null $productId  If provided, only sync this product (and its variants).
     * @return int Number of product/variant stock rows updated.
     */
    public function syncStockFromBatches(?int $productId = null): int
    {
        $count = 0;

        DB::transaction(function () use ($productId, &$count) {
            // --- Products: stock = SUM(remaining_qty) across all in-batches ---
            $productTotals = StockBatch::query()
                ->selectRaw('product_id')
                ->selectRaw('SUM(remaining_qty) as total_remaining')
                ->where('remaining_qty', '>', 0)
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');

            // --- Website cache (batch-wise mode only) ---
            $batchWise = (bool) config('pricing.batch_wise', false);
            $websiteTotals = collect();
            $activeBatches = collect();
            if ($batchWise) {
                // website_stock = SUM(remaining) of sellable (pos_enabled, not expired) batches
                $websiteTotals = StockBatch::query()
                    ->selectRaw('product_id')
                    ->selectRaw('SUM(remaining_qty) as total_remaining')
                    ->where('pos_enabled', 1)
                    ->where('remaining_qty', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('exp_date')->orWhere('exp_date', '>=', now()->toDateString());
                    })
                    ->when($productId, fn ($q) => $q->where('product_id', $productId))
                    ->groupBy('product_id')
                    ->get()
                    ->keyBy('product_id');

                // website_price = active batch selling_price
                $activeBatches = StockBatch::query()
                    ->where('is_active_for_website', 1)
                    ->when($productId, fn ($q) => $q->where('product_id', $productId))
                    ->get(['id', 'product_id', 'selling_price'])
                    ->keyBy('product_id');
            }

            Product::query()
                ->when($productId, fn ($q) => $q->where('id', $productId))
                ->get(['id', 'name', 'stock', 'new_price', 'website_price', 'website_stock'])
                ->each(function (Product $product) use ($productTotals, $websiteTotals, $activeBatches, $batchWise, &$count) {
                    // Products with no batch rows keep their existing stock untouched
                    if (!$productTotals->has($product->id)) {
                        return;
                    }
                    $computed = (int) $productTotals[$product->id]->total_remaining;
                    if ((int) $product->stock !== $computed) {
                        $product->update(['stock' => $computed]);
                        $count++;
                    }

                    if ($batchWise) {
                        $wStock = (int) ($websiteTotals[$product->id]->total_remaining ?? 0);
                        $active  = $activeBatches->get($product->id);
                        $wPrice  = $active ? (float) $active->selling_price : null;
                        if ($wPrice === null || $wPrice <= 0) {
                            $wPrice = (float) ($product->getRawOriginal('new_price') ?? 0);
                        }
                        if ((int) $product->website_stock !== $wStock
                            || (float) ($product->website_price ?? 0) !== $wPrice
                            || (float) ($product->getRawOriginal('new_price') ?? 0) !== $wPrice) {
                            $updates = ['website_stock' => $wStock, 'website_price' => $wPrice];
                            if ($wPrice > 0) {
                                $updates['new_price'] = $wPrice; // keep catalog sort/filter aligned
                            }
                            $product->update($updates);
                            $count++;
                        }
                    }
                });

            // --- Variants: product_variant_prices.stock = SUM(remaining_qty) per variant ---
            $variantTotals = StockBatch::query()
                ->selectRaw('variant_price_id')
                ->selectRaw('SUM(remaining_qty) as total_remaining')
                ->whereNotNull('variant_price_id')
                ->where('remaining_qty', '>', 0)
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->groupBy('variant_price_id')
                ->get()
                ->keyBy('variant_price_id');

            ProductVariantPrice::query()
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->get(['id', 'product_id', 'stock'])
                ->each(function (ProductVariantPrice $variant) use ($variantTotals, &$count) {
                    if (!$variantTotals->has($variant->id)) {
                        return;
                    }
                    $computed = (int) $variantTotals[$variant->id]->total_remaining;
                    if ((int) $variant->stock !== $computed) {
                        $variant->update(['stock' => $computed]);
                        $count++;
                    }
                });
        });

        return $count;
    }
}
