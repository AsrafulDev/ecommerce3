<?php

namespace App\Console\Commands;

use App\Models\BatchVariantPrice;
use App\Models\BatchWarrantyTier;
use App\Models\BatchWholesalePrice;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\StockBatch;
use App\Services\PricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the batch-wise pricing tables from the legacy product/variant/wholesale
 * price columns. Safe to re-run (idempotent upserts). Run BEFORE enabling
 * BATCH_WISE_PRICING so the website has an active batch for every stocked product.
 *
 *   php artisan pricing:backfill --dry-run
 *   php artisan pricing:backfill --product=21
 */
class BackfillBatchPricing extends Command
{
    protected $signature = 'pricing:backfill {--dry-run : Report only, no writes} {--product= : Limit to one product id}';

    protected $description = 'Backfill batch-wise pricing (selling_price, MRP, variant/wholesale/warranty, active website batch) from legacy columns';

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $onlyId   = $this->option('product') ? (int) $this->option('product') : null;

        $query = Product::with(['stockBatches', 'variantPrices', 'wholesalePrices', 'warrantyTiers']);
        if ($onlyId) {
            $query->whereKey($onlyId);
        }
        $products = $query->get();

        $this->info("Backfilling pricing for {$products->count()} product(s)" . ($dryRun ? ' — DRY RUN (no writes)' : ''));

        $stats = ['selling_price' => 0, 'mrp' => 0, 'active' => 0, 'variant' => 0, 'wholesale' => 0, 'warranty' => 0];
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            try {
                // Merge deltas ONLY when the whole product transaction committed
                $delta = DB::transaction(function () use ($product, $dryRun) {
                    return $this->backfillProduct($product, $dryRun);
                });
                foreach ($delta as $k => $v) {
                    $stats[$k] += $v;
                }
            } catch (\Throwable $e) {
                $this->error("\nProduct #{$product->id}: " . $e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Updated'], [
            ['selling_price (batch)', $stats['selling_price']],
            ['mrp (batch)', $stats['mrp']],
            ['active website batch', $stats['active']],
            ['batch_variant_prices', $stats['variant']],
            ['batch_wholesale_prices', $stats['wholesale']],
            ['batch_warranty_tiers', $stats['warranty']],
        ]);

        if (!$dryRun) {
            $this->info('Running stock:sync-from-batches to reconcile products.stock + website cache…');
            $this->call('stock:sync-from-batches', ['--product' => $onlyId]);
        }

        return self::SUCCESS;
    }

    private function backfillProduct(Product $product, bool $dryRun): array
    {
        $stats = ['selling_price' => 0, 'mrp' => 0, 'active' => 0, 'variant' => 0, 'wholesale' => 0, 'warranty' => 0];

        // 1) Per-batch base price + MRP (from products.new_price / old_price)
        foreach ($product->stockBatches as $batch) {
            $changes = [];
            if (!$batch->selling_price || (float) $batch->selling_price <= 0) {
                $changes['selling_price'] = (float) ($product->new_price ?? 0);
                $stats['selling_price']++;
            }
            if ($batch->mrp === null && (float) ($product->old_price ?? 0) > 0) {
                $changes['mrp'] = (float) $product->old_price;
                $stats['mrp']++;
            }
            if ($changes && !$dryRun) {
                $batch->update($changes);
            }
        }

        // 2) Active website batch = oldest (FIFO) sellable batch with stock
        $active = $product->stockBatches
            ->where('is_active_for_website', true)
            ->first();
        if (!$active) {
            $active = $product->stockBatches
                ->filter(fn ($b) => $b->remaining_qty > 0 && $b->pos_enabled)
                ->sortBy('created_at')
                ->first();
            if ($active && !$dryRun) {
                $product->stockBatches()->where('id', '!=', $active->id)->update(['is_active_for_website' => false]);
                $active->update(['is_active_for_website' => true, 'price_updated_at' => now()]);
            }
            if ($active) {
                $stats['active']++;
            }
        }
        $targetBatchId = $active?->id ?? $product->stockBatches->first()?->id;
        if (!$targetBatchId) {
            return $stats; // no batches — nothing to backfill
        }

        // 3) Variant prices → active batch (per product_variant_prices.price)
        foreach ($product->variantPrices as $vp) {
            $exists = BatchVariantPrice::where('stock_batch_id', $targetBatchId)
                ->where('variant_price_id', $vp->id)
                ->exists();
            if (!$exists && !$dryRun) {
                BatchVariantPrice::create([
                    'stock_batch_id'   => $targetBatchId,
                    'variant_price_id' => $vp->id,
                    'price'            => (float) ($vp->price ?? 0),
                    'old_price'        => (float) ($product->old_price ?? 0) > 0 ? (float) $product->old_price : null,
                    'stock'            => (int) ($vp->stock ?? 0),
                ]);
            }
            $stats['variant']++;
        }

        // 4) Wholesale tiers → active batch
        foreach ($product->wholesalePrices as $tier) {
            $exists = BatchWholesalePrice::where('stock_batch_id', $targetBatchId)
                ->where('variant_price_id', $tier->variant_id)
                ->where('min_quantity', $tier->min_quantity)
                ->exists();
            if (!$exists && !$dryRun) {
                BatchWholesalePrice::create([
                    'stock_batch_id'   => $targetBatchId,
                    'variant_price_id' => $tier->variant_id,
                    'min_quantity'     => $tier->min_quantity,
                    'max_quantity'     => $tier->max_quantity,
                    'wholesale_price'  => $tier->wholesale_price,
                ]);
            }
            $stats['wholesale']++;
        }

        // 5) Warranty tiers → active batch
        foreach ($product->warrantyTiers as $tier) {
            $exists = BatchWarrantyTier::where('stock_batch_id', $targetBatchId)
                ->where('variant_price_id', $tier->variant_id)
                ->where('warranty_tier_id', $tier->id)
                ->exists();
            if (!$exists && !$dryRun) {
                BatchWarrantyTier::create([
                    'stock_batch_id'   => $targetBatchId,
                    'variant_price_id' => $tier->variant_id,
                    'warranty_tier_id' => $tier->id,
                    'additional_cost'  => (float) ($tier->additional_cost ?? 0),
                    'is_active'        => (bool) $tier->is_active,
                ]);
            }
            $stats['warranty']++;
        }

        return $stats;
    }
}
