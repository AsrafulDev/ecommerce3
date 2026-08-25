<?php

namespace App\Services;

use App\Models\BatchVariantPrice;
use App\Models\BatchWholesalePrice;
use App\Models\BatchWarrantyTier;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\StockBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PricingService — the single source of truth for sellable prices.
 *
 * When config('pricing.batch_wise') is OFF it falls back to the legacy
 * resolution (products.new_price → variant price) so the upgrade is reversible.
 * When ON, every price is resolved from the batch-anchored tables.
 */
class PricingService
{
    public function isBatchWise(): bool
    {
        return (bool) config('pricing.batch_wise', false);
    }

    // ──────────────────────────────────────────────────────
    // Website batch selection (FIFO default + auto-advance)
    // ──────────────────────────────────────────────────────

    /**
     * The single batch the website shows & prices from.
     */
    public function activeWebsiteBatch(Product $product): ?StockBatch
    {
        if (!$this->isBatchWise()) {
            return null;
        }

        $active = StockBatch::where('product_id', $product->id)
            ->activeForWebsite()
            ->first();

        // Safety net: if the active batch is depleted and auto-advance is on,
        // lazily move to the next FIFO batch with stock.
        if ($active && $active->remaining_qty <= 0 && $active->auto_advance) {
            return $this->advanceActiveBatchIfDepleted($product);
        }

        return $active;
    }

    /**
     * Force the website batch (admin override).
     * Clears the flag on all other batches for the product.
     */
    public function setActiveWebsiteBatch(Product $product, int $batchId): ?StockBatch
    {
        if (!$this->isBatchWise()) {
            return null;
        }

        $batch = StockBatch::where('id', $batchId)->where('product_id', $product->id)->first();
        if (!$batch) {
            return null;
        }

        DB::transaction(function () use ($product, $batch) {
            StockBatch::where('product_id', $product->id)
                ->where('id', '!=', $batch->id)
                ->update(['is_active_for_website' => false]);

            $batch->is_active_for_website = true;
            $batch->price_updated_at = now();
            $batch->price_updated_by = auth('admin')->id();
            $batch->save();

            $this->refreshProductCache($product);
        });

        log_activity('pricing', 'activate_batch', "Website batch activated: #{$batch->id} for product #{$product->id}", $batch, ['batch_id' => $batch->id, 'product_id' => $product->id]);

        return $batch;
    }

    /**
     * If the active website batch is depleted, advance to the next FIFO batch
     * with stock. Idempotent.
     */
    public function advanceActiveBatchIfDepleted(Product $product): ?StockBatch
    {
        if (!$this->isBatchWise()) {
            return null;
        }

        $active = StockBatch::where('product_id', $product->id)->activeForWebsite()->first();

        // Nothing to do if the active batch still has stock (respect manual override).
        if ($active && $active->remaining_qty > 0) {
            return null;
        }

        // Admin pinned a batch with stock → keep it (do not auto-advance).
        if ($active && $active->auto_advance === false) {
            return $active;
        }

        // Next = oldest (FIFO) sellable batch with remaining stock.
        $next = StockBatch::where('product_id', $product->id)
            ->sellable()
            ->orderBy('mfg_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($next) {
            return $this->setActiveWebsiteBatch($product, $next->id);
        }

        // No sellable batch left → clear the flag entirely (website = out of stock).
        StockBatch::where('product_id', $product->id)->update(['is_active_for_website' => false]);
        return null;
    }

    /**
     * FIFO allocation of a quantity across sellable batches.
     * e.g. b1=3, b2=10, qty=8 → [[b1,3],[b2,5]].
     *
     * @return array<int, array{batch: StockBatch, qty: int}>
     */
    public function websiteAllocation(Product $product, int $qty): array
    {
        if (!$this->isBatchWise()) {
            return [];
        }

        $alloc = [];
        $need  = $qty;

        $batches = StockBatch::where('product_id', $product->id)
            ->sellable()
            ->orderBy('mfg_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            if ($need <= 0) {
                break;
            }
            $take = min($need, (int) $batch->remaining_qty);
            $alloc[] = ['batch' => $batch, 'qty' => $take];
            $need -= $take;
        }

        return $alloc;
    }

    // ──────────────────────────────────────────────────────
    // Price resolution
    // ──────────────────────────────────────────────────────

    /**
     * Sell price for a given channel/batch/variant.
     *
     * @param string $channel 'website'|'pos'|'admin'
     */
    public function price(Product $product, ?int $batchId = null, ?int $variantId = null, string $channel = 'website'): float
    {
        if (!$this->isBatchWise()) {
            return $this->legacyPrice($product, $variantId);
        }

        $batch = $this->resolveBatch($product, $batchId, $channel);
        if (!$batch) {
            return 0; // caller renders OUT OF STOCK
        }

        // Variant-specific price for this batch
        if ($variantId) {
            $bvp = BatchVariantPrice::where('stock_batch_id', $batch->id)
                ->where('variant_price_id', $variantId)
                ->first();
            if ($bvp && (float) $bvp->price > 0) {
                return (float) $bvp->price;
            }
        }

        // Batch base price
        if ($batch->selling_price > 0) {
            return (float) $batch->selling_price;
        }

        // Legacy fallback
        return $this->legacyPrice($product, $variantId);
    }

    /**
     * Compare-at / MRP price — nullable.
     */
    public function mrp(Product $product, ?int $batchId = null, ?int $variantId = null): ?float
    {
        if (!$this->isBatchWise()) {
            $legacy = (float) ($product->old_price ?? 0);
            return $legacy > 0 ? $legacy : null;
        }

        $batch = $this->resolveBatch($product, $batchId, 'website');
        if (!$batch) {
            return null;
        }

        if ($variantId) {
            $bvp = BatchVariantPrice::where('stock_batch_id', $batch->id)
                ->where('variant_price_id', $variantId)
                ->first();
            if ($bvp && $bvp->old_price !== null && (float) $bvp->old_price > 0) {
                return (float) $bvp->old_price;
            }
        }

        if ($batch->mrp !== null && (float) $batch->mrp > 0) {
            return (float) $batch->mrp;
        }

        $legacy = (float) ($product->old_price ?? 0);
        return $legacy > 0 ? $legacy : null;
    }

    /**
     * Wholesale discount amount for a qty on a batch/variant.
     * (Keeps legacy semantics: a discount amount subtracted from the sell price.)
     */
    public function wholesale(Product $product, int $qty, ?int $batchId = null, ?int $variantId = null): float
    {
        if (!$this->isBatchWise()) {
            return $this->legacyWholesale($product, $qty, $variantId);
        }

        $batch = $this->resolveBatch($product, $batchId, 'website');
        if (!$batch) {
            return 0;
        }

        // Priority 1: variant-specific tier
        if ($variantId) {
            $tier = BatchWholesalePrice::where('stock_batch_id', $batch->id)
                ->where('variant_price_id', $variantId)
                ->where('min_quantity', '<=', $qty)
                ->where(function ($q) use ($qty) {
                    $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $qty);
                })
                ->orderByDesc('min_quantity')
                ->first();
            if ($tier) {
                return (float) $tier->wholesale_price;
            }
        }

        // Priority 2: all-variant tier
        $tier = BatchWholesalePrice::where('stock_batch_id', $batch->id)
            ->whereNull('variant_price_id')
            ->where('min_quantity', '<=', $qty)
            ->where(function ($q) use ($qty) {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $qty);
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $tier ? (float) $tier->wholesale_price : 0;
    }

    /**
     * Warranty surcharge for a tier on a batch/variant.
     * Falls back to the tier catalog's own additional_cost when no batch override.
     */
    public function warrantyAdjustment(Product $product, int $tierId, ?int $batchId = null, ?int $variantId = null): float
    {
        if (!$this->isBatchWise()) {
            return $this->legacyWarranty($tierId);
        }

        $batch = $this->resolveBatch($product, $batchId, 'website');
        if (!$batch) {
            return $this->legacyWarranty($tierId);
        }

        $override = BatchWarrantyTier::where('stock_batch_id', $batch->id)
            ->where('warranty_tier_id', $tierId)
            ->where('is_active', true)
            ->where(function ($q) use ($variantId) {
                $q->whereNull('variant_price_id');
                if ($variantId) {
                    $q->orWhere('variant_price_id', $variantId);
                }
            })
            ->orderByDesc('variant_price_id') // specific variant wins over null
            ->first();

        if ($override) {
            return (float) $override->additional_cost;
        }

        return $this->legacyWarranty($tierId);
    }

    // ──────────────────────────────────────────────────────
    // Stock / sellability
    // ──────────────────────────────────────────────────────

    public function sellableStock(Product $product, string $channel = 'website', ?int $batchId = null, ?int $variantId = null): int
    {
        if (!$this->isBatchWise()) {
            return (int) $product->stock;
        }

        if ($channel === 'pos' && $batchId) {
            $batch = StockBatch::find($batchId);
            if (!$batch) {
                return 0;
            }
            if ($variantId) {
                $bvp = BatchVariantPrice::where('stock_batch_id', $batch->id)
                    ->where('variant_price_id', $variantId)->first();
                return $bvp ? (int) $bvp->stock : 0;
            }
            return (int) $batch->remaining_qty;
        }

        // website (or pos without a batch) → sum of sellable batches
        $query = StockBatch::where('product_id', $product->id)->sellable();
        if ($variantId) {
            $query->whereHas('variantPrices', fn ($q) => $q->where('variant_price_id', $variantId));
        }
        return (int) $query->sum('remaining_qty');
    }

    public function isWebsiteSellable(Product $product): bool
    {
        if (!$this->isBatchWise()) {
            return (int) $product->stock > 0 && $product->getResolvedPublishStatusAttribute() === 'active';
        }
        // Strict rule: the website is sellable only when an active website batch
        // exists with stock (no active batch ⇒ Out of Stock, even if POS has stock).
        $active = $this->activeWebsiteBatch($product);
        return $active !== null && (int) $active->remaining_qty > 0;
    }

    public function posBatches(Product $product): Collection
    {
        if (!$this->isBatchWise()) {
            return collect();
        }
        return StockBatch::where('product_id', $product->id)->sellable()->get();
    }

    // ──────────────────────────────────────────────────────
    // Cache maintenance (products.website_price / website_stock)
    // ──────────────────────────────────────────────────────

    public function refreshProductCache(Product $product): void
    {
        if (!config('pricing.cache_website_price', true) || !$this->isBatchWise()) {
            return;
        }

        $active = StockBatch::where('product_id', $product->id)->activeForWebsite()->first();
        $stock  = StockBatch::where('product_id', $product->id)->sellable()->sum('remaining_qty');

        $price = null;
        if ($active) {
            $price = (float) ($active->selling_price ?? 0);
            if ($price <= 0) {
                $price = (float) ($product->getRawOriginal('new_price') ?? 0);
            }
        }

        $product->website_price = $price;
        $product->website_stock = (int) $stock;
        // Sync the legacy sell-price column too, so catalog sort / price filters
        // (which query `new_price` directly) reflect the active batch price
        // without rewriting every query.
        if ($price !== null && (float) $price > 0) {
            $product->new_price = $price;
        }
        $product->saveQuietly();
    }

    // ──────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────

    protected function resolveBatch(Product $product, ?int $batchId, string $channel): ?StockBatch
    {
        if ($batchId) {
            $batch = StockBatch::find($batchId);
            return ($batch && $batch->product_id == $product->id) ? $batch : null;
        }
        if ($channel === 'website') {
            return $this->activeWebsiteBatch($product);
        }
        return null;
    }

    protected function legacyPrice(Product $product, ?int $variantId): float
    {
        if ($variantId) {
            $v = ProductVariantPrice::find($variantId);
            if ($v && (float) $v->price > 0) {
                return (float) $v->price;
            }
        }
        // Use the raw stored column — not the batch-aware accessor — to avoid recursion.
        $rawNew = (float) ($product->getRawOriginal('new_price') ?? 0);
        if ($rawNew > 0) {
            return $rawNew;
        }
        return (float) ($product->old_price ?? 0);
    }

    protected function legacyWholesale(Product $product, int $qty, ?int $variantId): float
    {
        $tier = \App\Models\ProductWholesalePrice::where('product_id', $product->id)
            ->where(function ($q) use ($variantId) {
                $q->whereNull('variant_id');
                if ($variantId) {
                    $q->orWhere('variant_id', $variantId);
                }
            })
            ->where('min_quantity', '<=', $qty)
            ->where(function ($q) use ($qty) {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $qty);
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $tier ? (float) $tier->wholesale_price : 0;
    }

    protected function legacyWarranty(int $tierId): float
    {
        $tier = \App\Models\ProductWarrantyTier::find($tierId);
        return $tier ? (float) ($tier->additional_cost ?? 0) : 0;
    }
}
