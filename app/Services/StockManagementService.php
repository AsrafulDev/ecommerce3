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
        return $global->default_costing_method ?? 'average';
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
            'mfg_date'        => $data['mfg_date'] ?? null,
            'exp_date'        => $data['exp_date'] ?? null,
            'type'            => 'in',
            'reference_type'  => $data['reference_type'] ?? 'purchase',
            'reference_id'    => $data['reference_id'] ?? null,
        ]);

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
     * @return array  ['cogs' => float, 'batch_details' => [...], 'remaining' => int]
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function stockOut(Product $product, int $qty, array $reference = []): array
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

        if ($method === 'average') {
            // Average Cost: COGS = current purchase_price * qty
            $avgCost = (float) ($product->purchase_price ?? 0);
            $totalCogs = $avgCost * $qty;

            // Deduct proportionally from all batches (simplified: FIFO order but with avg cost)
            $batches = $this->getAvailableBatches($product, 'fifo');
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

            $batches = $this->getAvailableBatches($product, $method);
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

        // Create outflow batch record for traceability
        StockBatch::create([
            'product_id'       => $product->id,
            'quantity'         => -$qty,
            'remaining_qty'    => 0,
            'unit_cost'        => 0,
            'type'             => 'out',
            'reference_type'   => $reference['type'] ?? 'sale',
            'reference_id'     => $reference['id'] ?? null,
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
        ?int $variantPriceId = null
    ): StockAdjustment {
        $currentStock = (int) $product->stock;
        $newStock = $currentStock;

        if ($type === 'addition') {
            $newStock = $currentStock + $qty;
            // Treat as stock-in
            $this->stockIn($product, [
                'quantity'         => $qty,
                'unit_cost'        => (float) ($product->purchase_price ?? 0),
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
}
