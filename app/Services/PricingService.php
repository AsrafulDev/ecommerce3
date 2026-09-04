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
use Illuminate\Support\Facades\Schema;

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
        // Batch-wise is the system default (no BATCH_WISE_PRICING toggle).
        // (Tests may still override config('pricing.batch_wise') locally.)
        return (bool) config('pricing.batch_wise', true);
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
            // pos_enabled = false disables a batch for BOTH web and POS (D3) —
            // a flagged-but-disabled batch must never be shown/priced on the website.
            ->where('pos_enabled', true)
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
        // Works with BATCH_WISE_PRICING on OR off: a batch marked as the website
        // batch should always drive the storefront (flag only changes whether the
        // full batch-price engine reads it vs. the mirrored product columns).
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
     * Backward-compatible wrapper around allocate('FIFO').
     *
     * @return array<int, array{batch: StockBatch, qty: int}>
     */
    public function websiteAllocation(Product $product, int $qty): array
    {
        return $this->allocate($product, $qty, null, 'FIFO');
    }

    // ──────────────────────────────────────────────────────
    // ⭐ Storefront batch display & allocation API
    //    (Spec: eligible batches → price range → allocate)
    // ──────────────────────────────────────────────────────

    /**
     * Per-product stock allocation method used by the storefront:
     * FIFO (oldest first) | LIFO (newest first) | AVG (weighted-average pricing).
     */
    public function allocationMethod(Product $product): string
    {
        if (!Schema::hasColumn('products', 'allocation_method')) {
            return 'FIFO';
        }
        $method = strtoupper((string) ($product->allocation_method ?? 'FIFO'));
        return in_array($method, ['FIFO', 'LIFO', 'AVG'], true) ? $method : 'FIFO';
    }

    /**
     * Eligible (active + in-stock + unexpired) batches for a product.
     *
     * When $variantId is given, only batches applicable to that combination are
     * returned ([Spec §25]):
     *   - is_all_variants = true            → ALL-variant fallback pool
     *   - variant_price_id == $variantId    → batch bought for this specific variant
     *   - has a BatchVariantPrice row for V → explicitly priced for this variant
     *   - true product-level batch          → variant_price_id null AND no per-variant rows
     * Batches scoped to OTHER specific variants are excluded.
     *
     * @return Collection<int, StockBatch> ordered FIFO (oldest batch first)
     */
    public function eligibleBatches(Product $product, ?int $variantId = null): Collection
    {
        if (!$this->isBatchWise()) {
            return collect();
        }

        $query = StockBatch::where('product_id', $product->id)->sellable();

        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                // 1) ALL-variant batch → shared pool for every combination.
                $q->where('is_all_variants', true)
                  // 2) batch explicitly bought for THIS variant (variant_price_id set,
                  //    is_all_variants=false) → serves ONLY this variant — bvp rows on a
                  //    specific batch must NOT make it serve other variants.
                  ->orWhere('variant_price_id', $variantId)
                  // 3) product-level batch (variant_price_id null): serves this variant
                  //    when it lists the variant via batch_variant_prices, or when it has
                  //    no per-variant structure at all (pure shared pool).
                  ->orWhere(function ($pool) use ($variantId) {
                      $pool->whereNull('variant_price_id')
                           ->where(function ($b) use ($variantId) {
                               $b->whereDoesntHave('variantPrices')
                                 ->orWhereHas('variantPrices', fn ($vp) => $vp->where('variant_price_id', $variantId));
                           });
                  });
            });
        }

        return $query->orderBy('mfg_date')->orderBy('created_at')->orderBy('id')->get();
    }

    /**
     * Stock-aware price range across eligible batches ([Spec §4/§22/§33]).
     *
     * Returns:
     *   min_sale / max_sale : min & max sellable batch price (0 when none)
     *   min_mrp  / max_mrp  : min & max MRP (null when no batch carries MRP)
     *   count               : number of priced eligible batches
     * Exhausted / expired / disabled batches are excluded (never shown).
     */
    public function priceRange(Product $product, ?int $variantId = null): array
    {
        $range = [
            'min_sale' => 0.0,
            'max_sale' => 0.0,
            'min_mrp'  => null,
            'max_mrp'  => null,
            'count'    => 0,
        ];

        if (!$this->isBatchWise()) {
            // Legacy fallback: a single product-level price.
            $sale = $this->legacyPrice($product, $variantId);
            if ($sale > 0) {
                $range['min_sale'] = $range['max_sale'] = round($sale, 2);
                $range['count'] = 1;
            }
            return $range;
        }

        $sales = [];
        $mrps  = [];
        foreach ($this->eligibleBatches($product, $variantId) as $batch) {
            $sale = $this->batchSalePrice($batch, $variantId);
            if ($sale > 0) {
                $sales[] = $sale;
            }
            $mrp = $this->batchMrpValue($batch, $variantId);
            if ($mrp !== null && $mrp > 0) {
                $mrps[] = $mrp;
            }
        }

        if ($sales) {
            $range['min_sale'] = round((float) min($sales), 2);
            $range['max_sale'] = round((float) max($sales), 2);
        }
        if ($mrps) {
            $range['min_mrp'] = round((float) min($mrps), 2);
            $range['max_mrp'] = round((float) max($mrps), 2);
        }
        $range['count'] = count($sales);

        return $range;
    }

    /**
     * Allocate a requested quantity across eligible batches ([Spec §5–§8]).
     *
     * FIFO → consume the oldest eligible batch first.
     * LIFO → consume the newest eligible batch first.
     * AVG  → physical consumption stays oldest-first, but every portion is priced
     *        at the quantity-weighted average sale price of the eligible batches.
     *
     * @return array<int, array{batch: StockBatch, qty: int, unit_price: float, mrp: float|null}>
     */
    public function allocate(Product $product, int $qty, ?int $variantId = null, ?string $method = null): array
    {
        if (!$this->isBatchWise()) {
            return [];
        }

        $method = strtoupper((string) ($method ?: $this->allocationMethod($product)));
        $method = in_array($method, ['FIFO', 'LIFO', 'AVG'], true) ? $method : 'FIFO';

        // Eligible batches arrive FIFO-ordered (oldest first).
        $batches = $this->eligibleBatches($product, $variantId)->values();

        // AVG pricing rule: quantity-weighted average of eligible batch prices.
        $avgPrice = null;
        if ($method === 'AVG') {
            $totalQty  = 0;
            $totalVal  = 0.0;
            foreach ($batches as $b) {
                $q = max(0, (int) $b->remaining_qty);
                $totalQty += $q;
                $totalVal += $q * $this->batchSalePrice($b, $variantId);
            }
            if ($totalQty > 0) {
                $avgPrice = $totalVal / $totalQty;
            }
            // Physical consumption is still FIFO for stock accounting.
            $method = 'FIFO';
        }

        if ($method === 'LIFO') {
            $batches = $batches->reverse()->values();
        }

        $alloc = [];
        $need  = max(0, (int) $qty);
        foreach ($batches as $batch) {
            if ($need <= 0) {
                break;
            }
            $take = min($need, (int) $batch->remaining_qty);
            $alloc[] = [
                'batch'      => $batch,
                'qty'        => $take,
                'unit_price' => $avgPrice !== null ? round($avgPrice, 2) : $this->batchSalePrice($batch, $variantId),
                'mrp'        => $this->batchMrpValue($batch, $variantId),
            ];
            $need -= $take;
        }

        return $alloc;
    }

    /**
     * Weighted per-unit sale price of a requested quantity across its eligible
     * batches (used by per-batch billing — Spec §5/§8).
     *
     * Billing a whole cart line at this unit price yields exactly Σ(qty_i × price_i)
     * (e.g. b1=3@10, b2=17@12, qty 20 → unit 11.7 → total 234). Only returned when
     * the allocation fully covers the requested quantity; otherwise 0 (caller falls
     * back to the active-batch price).
     */
    public function weightedAllocationUnit(Product $product, int $qty, ?int $variantId = null, ?string $method = null): float
    {
        if (!$this->isBatchWise() || $qty <= 0) {
            return 0.0;
        }
        $alloc = $this->allocate($product, $qty, $variantId, $method);
        $qtySum = 0;
        $valSum = 0.0;
        foreach ($alloc as $portion) {
            $qtySum += $portion['qty'];
            $valSum += $portion['qty'] * $portion['unit_price'];
        }
        if ($qtySum < $qty || $qtySum <= 0) {
            return 0.0; // cannot fully allocate at these prices
        }
        return round($valSum / $qtySum, 2);
    }

    /**
     * Attach batch price-range attributes to a set of products using ONE grouped
     * query (no N+1). Accepts a Collection, Paginator or a single Product.
     *
     * Attributes set on each product (views share one code path):
     *   price_min / price_max   : min & max sellable batch sale price
     *   mrp_min  / mrp_max      : min & max batch MRP (null when absent)
     *   price_single            : true when min == max (render a single price)
     *   stock_sellable          : sum of sellable batch qty
     * When batch-wise is OFF these fall back to the static product columns
     * (new_price / old_price / stock), so non-batch pages still work.
     */
    public function attachCatalogRanges($products): void
    {
        if ($products === null) {
            return;
        }

        if ($products instanceof Product) {
            $items = collect([$products]);
        } elseif (method_exists($products, 'getCollection')) { // LengthAwarePaginator
            $items = $products->getCollection();
        } elseif ($products instanceof Collection) {
            $items = $products;
        } else {
            return;
        }

        $items = $items->filter(fn ($p) => $p instanceof Product)->values();
        if ($items->isEmpty()) {
            return;
        }

        if (!$this->isBatchWise()) {
            // Legacy mode: single product price from the static columns.
            foreach ($items as $p) {
                $sale = (float) ($p->getRawOriginal('new_price') ?? 0);
                $mrp  = (float) ($p->old_price ?? 0);
                $p->setAttribute('price_min', $sale > 0 ? $sale : 0.0);
                $p->setAttribute('price_max', $sale > 0 ? $sale : 0.0);
                $p->setAttribute('mrp_min', $mrp > 0 ? $mrp : null);
                $p->setAttribute('mrp_max', $mrp > 0 ? $mrp : null);
                $p->setAttribute('price_single', true);
                $p->setAttribute('stock_sellable', (int) ($p->stock ?? 0));
            }
            return;
        }

        // Batch-wise: one query for every sellable batch of the loaded products.
        $rows = StockBatch::whereIn('product_id', $items->pluck('id'))
            ->sellable()
            ->get(['product_id', 'selling_price', 'mrp', 'remaining_qty'])
            ->groupBy('product_id');

        foreach ($items as $p) {
            $batches = $rows->get($p->id, collect());

            $sales = $batches
                ->map(fn ($b) => (float) $b->selling_price)
                ->filter(fn ($v) => $v > 0);

            $mrps = $batches
                ->map(fn ($b) => $b->mrp !== null ? (float) $b->mrp : null)
                ->filter();

            $p->setAttribute('price_min', $sales->isNotEmpty() ? round($sales->min(), 2) : 0.0);
            $p->setAttribute('price_max', $sales->isNotEmpty() ? round($sales->max(), 2) : 0.0);
            $p->setAttribute('mrp_min', $mrps->isNotEmpty() ? round($mrps->min(), 2) : null);
            $p->setAttribute('mrp_max', $mrps->isNotEmpty() ? round($mrps->max(), 2) : null);
            $p->setAttribute('price_single', $sales->isNotEmpty() && $sales->min() == $sales->max());
            $p->setAttribute('stock_sellable', (int) $batches->sum('remaining_qty'));
        }
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

        $price = $this->batchSalePrice($batch, $variantId);
        return $price > 0 ? $price : $this->legacyPrice($product, $variantId);
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

        $mrp = $this->batchMrpValue($batch, $variantId);
        if ($mrp !== null && $mrp > 0) {
            return $mrp;
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

    /**
     * The maximum quantity of this product (optionally: this batch/variant) that
     * may still be added to a cart / order. Returns null when the product has
     * `allow_negative_stock` enabled — meaning no ceiling should be enforced —
     * so this mirrors the same allowNegative bypass StockManagementService::
     * stockOut() uses at order-save time. Cart layers (POS + storefront) MUST
     * use this — not sellableStock() directly — to decide whether to block a
     * qty increase, so a product without negative stock allowed can never be
     * added past real stock, while one that allows it is never capped.
     */
    public function maxOrderableQty(Product $product, string $channel = 'website', ?int $batchId = null, ?int $variantId = null): ?int
    {
        if ((bool) ($product->allow_negative_stock ?? false)) {
            return null;
        }

        return $this->sellableStock($product, $channel, $batchId, $variantId);
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
        // Runs for both batch-wise and legacy mode: when a website batch is active
        // its selling price/MRP are mirrored onto the product (new_price/old_price +
        // website_price/website_stock), so the storefront always reflects the batch.
        if (!config('pricing.cache_website_price', true)) {
            return;
        }

        // pos_enabled=false disables a batch for BOTH web and POS (D3)
        $active = StockBatch::where('product_id', $product->id)
            ->activeForWebsite()
            ->where('pos_enabled', true)
            ->first();
        $stock  = StockBatch::where('product_id', $product->id)->sellable()->sum('remaining_qty');

        $price = null;
        $mrp   = null;
        if ($active) {
            $price = $this->batchSalePrice($active, null);
            if ($price <= 0) {
                $price = (float) ($product->getRawOriginal('new_price') ?? 0);
            }
            $mrp = $this->batchMrpValue($active, null);
        }

        $product->website_price = $price;
        $product->website_stock = (int) $stock;
        // Sync the legacy sell-price column too, so catalog sort / price filters
        // (which query `new_price` directly) reflect the active batch price
        // without rewriting every query.
        if ($price !== null && (float) $price > 0) {
            $product->new_price = $price;
        }
        // Mirror the active batch MRP into old_price so <del> strikethrough
        // pricing and discount badges also reflect the batch (not stale admin input).
        if ($mrp !== null && (float) $mrp > 0) {
            $product->old_price = $mrp;
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

    /**
     * Sell price of ONE batch for a variant (shared by price/priceRange/allocate).
     * Priority: batch_variant_prices override → batch selling_price.
     * Returns 0.0 when the batch carries no sellable price.
     */
    protected function batchSalePrice(StockBatch $batch, ?int $variantId = null): float
    {
        if ($variantId) {
            $bvp = BatchVariantPrice::where('stock_batch_id', $batch->id)
                ->where('variant_price_id', $variantId)
                ->first();
            if ($bvp && (float) $bvp->price > 0) {
                return (float) $bvp->price;
            }
        }

        return (float) $batch->selling_price;
    }

    /**
     * MRP / compare-at price of ONE batch for a variant (nullable).
     * Priority: batch_variant_prices old_price → batch mrp.
     */
    protected function batchMrpValue(StockBatch $batch, ?int $variantId = null): ?float
    {
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
