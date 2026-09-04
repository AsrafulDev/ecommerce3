<?php

namespace App\Console\Commands;

use App\Models\BatchSnList;
use App\Models\BatchWholesalePrice;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\ProductVariantPrice;
use App\Models\StockBatch;
use App\Models\SupplierWarranty;
use App\Models\WarrantySale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the Phase-1 model columns from existing data (idempotent, re-runnable):
 *
 *   php artisan model:backfill --dry-run            # report only
 *   php artisan model:backfill --product=21         # one product
 *   php artisan model:backfill --only=sn            # run one section only
 *   php artisan model:backfill                      # everything
 *
 * Sections:
 *   variant  → product_variant_prices.image from productimages (first per color+size)
 *   cost     → stock_batches.total_cost = quantity × unit_cost (in-type batches)
 *   flags    → stock_batches.has_purchase_warranty / has_sell_warranty / has_wholesale
 *   sn       → stock_batches.sn_stock/sn_sold → batch_sn_lists rows
 */
class BackfillModelFields extends Command
{
    protected $signature = 'model:backfill
        {--dry-run : Report only, no writes}
        {--product= : Limit to one product id}
        {--only= : Comma list of sections: variant,cost,flags,sn (default: all)}';

    protected $description = 'Backfill Phase-1 model columns (variant image, batch total_cost, has_* flags, SN list) from existing data';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyId = $this->option('product') ? (int) $this->option('product') : null;
        $only   = $this->option('only');
        $wanted = $only
            ? array_map('trim', explode(',', mb_strtolower($only)))
            : ['variant', 'cost', 'flags', 'sn'];
        $wanted = array_intersect($wanted, ['variant', 'cost', 'flags', 'sn']);

        $products = Product::with(['stockBatches', 'variantPrices'])->when($onlyId, fn ($q) => $q->whereKey($onlyId))->get();

        $this->info("Backfilling model fields for {$products->count()} product(s)" . ($dryRun ? ' — DRY RUN (no writes)' : '') . ' [sections: ' . implode(',', $wanted) . ']');

        $stats = ['variant_image' => 0, 'total_cost' => 0, 'has_pw' => 0, 'has_sw' => 0, 'has_ws' => 0, 'sn_list' => 0];
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            try {
                $delta = DB::transaction(function () use ($product, $wanted, $dryRun) {
                    return $this->backfillProduct($product, $wanted, $dryRun);
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
            ['variant image', $stats['variant_image']],
            ['batch total_cost', $stats['total_cost']],
            ['has_purchase_warranty flag', $stats['has_pw']],
            ['has_sell_warranty flag', $stats['has_sw']],
            ['has_wholesale flag', $stats['has_ws']],
            ['batch_sn_lists rows', $stats['sn_list']],
        ]);

        return self::SUCCESS;
    }

    private function backfillProduct(Product $product, array $wanted, bool $dryRun): array
    {
        $stats = ['variant_image' => 0, 'total_cost' => 0, 'has_pw' => 0, 'has_sw' => 0, 'has_ws' => 0, 'sn_list' => 0];

        // ── variant: first matching productimage → product_variant_prices.image
        if (in_array('variant', $wanted, true)) {
            foreach ($product->variantPrices as $vp) {
                if (!empty($vp->image)) {
                    continue;
                }
                $img = Productimage::where('product_id', $product->id)
                    ->where(function ($q) use ($vp) {
                        $q->when(
                            $vp->color_id,
                            fn ($qq) => $qq->where('color_id', $vp->color_id),
                            fn ($qq) => $qq->whereNull('color_id')
                        );
                    })
                    ->where(function ($q) use ($vp) {
                        $q->when(
                            $vp->size_id,
                            fn ($qq) => $qq->where('size_id', $vp->size_id),
                            fn ($qq) => $qq->whereNull('size_id')
                        );
                    })
                    ->orderBy('id')
                    ->value('image');
                if ($img && !$dryRun) {
                    $vp->update(['image' => $img]);
                }
                if ($img) {
                    $stats['variant_image']++;
                }
            }
        }

        // ── cost: stock_batches.total_cost = quantity × unit_cost (in-type only)
        if (in_array('cost', $wanted, true)) {
            foreach ($product->stockBatches as $batch) {
                if ($batch->total_cost > 0) {
                    continue;
                }
                if ($batch->type !== 'in' && $batch->quantity <= 0) {
                    continue;
                }
                $total = round(((float) $batch->quantity) * ((float) ($batch->unit_cost ?? 0)), 2);
                if ($total > 0 && !$dryRun) {
                    $batch->update(['total_cost' => $total]);
                }
                if ($total > 0) {
                    $stats['total_cost']++;
                }
            }
        }

        // ── flags: has_purchase_warranty / has_sell_warranty / has_wholesale
        if (in_array('flags', $wanted, true)) {
            foreach ($product->stockBatches as $batch) {
                $changes = [];

                if (!$batch->has_purchase_warranty && $this->hasPurchaseWarranty($batch)) {
                    $changes['has_purchase_warranty'] = true;
                    $stats['has_pw']++;
                }
                if (!$batch->has_sell_warranty && $this->hasSellWarranty($batch)) {
                    $changes['has_sell_warranty'] = true;
                    $stats['has_sw']++;
                }
                if (!$batch->has_wholesale && $this->hasWholesale($batch)) {
                    $changes['has_wholesale'] = true;
                    $stats['has_ws']++;
                }

                if ($changes && !$dryRun) {
                    $batch->update($changes);
                }
            }
        }

        // ── sn: stock_batches.sn_stock/sn_sold → batch_sn_lists
        if (in_array('sn', $wanted, true)) {
            foreach ($product->stockBatches as $batch) {
                $stock = is_array($batch->sn_stock) ? $batch->sn_stock : [];
                $sold  = is_array($batch->sn_sold) ? $batch->sn_sold : [];
                if (!$stock && !$sold) {
                    continue;
                }
                $exists = BatchSnList::where('batch_id', $batch->id)->exists();
                if ($exists) {
                    continue;
                }
                if (!$dryRun) {
                    BatchSnList::create([
                        'product_id' => $product->id,
                        'variant_id' => $batch->variant_price_id,
                        'purchase_id' => $batch->purchase_id,
                        'batch_id'   => $batch->id,
                        'stock_sn'   => $stock,
                        'sold_sn'    => $sold,
                    ]);
                }
                $stats['sn_list']++;
            }
        }

        return $stats;
    }

    private function hasPurchaseWarranty(StockBatch $batch): bool
    {
        if (SupplierWarranty::where('batch_id', $batch->id)->exists()) {
            return true;
        }
        // Fallback for rows created before batch_id was recorded: same purchase+product
        if (!$batch->purchase_id) {
            return false;
        }
        return SupplierWarranty::where('product_id', $batch->product_id)
            ->whereHas('purchaseItem', fn ($q) => $q->where('purchase_id', $batch->purchase_id))
            ->exists();
    }

    private function hasSellWarranty(StockBatch $batch): bool
    {
        if (WarrantySale::where('stock_batch_id', $batch->id)->exists()) {
            return true;
        }
        // Fallback: same purchase sold with a customer warranty (no batch recorded)
        if (!$batch->purchase_id) {
            return false;
        }
        return WarrantySale::where('product_id', $batch->product_id)
            ->where('purchase_id', $batch->purchase_id)
            ->exists();
    }

    private function hasWholesale(StockBatch $batch): bool
    {
        return BatchWholesalePrice::where('stock_batch_id', $batch->id)->exists();
    }
}
