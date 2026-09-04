<?php

namespace Tests\Feature;

use App\Models\BatchVariantPrice;
use App\Models\BatchWholesalePrice;
use App\Models\BatchWarrantyTier;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\ProductWarrantyTier;
use App\Models\StockBatch;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Batch-Wise Pricing Engine — core resolution tests.
 *
 * Runs against an isolated in-memory SQLite DB (never the live MySQL), so it is
 * safe to execute with `php artisan test`.
 */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Runs against the in-memory SQLite DB from .env.testing (never live MySQL).
        parent::setUp();

        // Batch-wise pricing engine must be ON for these tests (no reliance on an
        // external BATCH_WISE_PRICING env var — full `php artisan test` stays green).
        config(['pricing.batch_wise' => true]);
        config(['pricing.cache_website_price' => false]);
    }

    protected function makeProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'name'           => 'Test Product',
            'slug'           => 'test-product-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'TP-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ], $attrs));
    }

    protected function makeBatch(Product $product, int $remaining, ?float $sell = null, bool $active = false): StockBatch
    {
        return StockBatch::create([
            'product_id'           => $product->id,
            'quantity'             => $remaining,
            'remaining_qty'        => $remaining,
            'unit_cost'            => 80,
            'selling_price'        => $sell,
            'pos_enabled'          => true,
            'auto_advance'         => true,
            'is_active_for_website'=> $active,
        ]);
    }

    public function test_price_uses_the_active_website_batch(): void
    {
        $product = $this->makeProduct();
        $b1 = $this->makeBatch($product, 3, 150.00, true);
        $this->makeBatch($product, 10, 190.00);

        $svc = app(PricingService::class);

        $this->assertSame($b1->id, $svc->activeWebsiteBatch($product)?->id);
        $this->assertEquals(150.00, $svc->price($product, null, null, 'website'));
    }

    public function test_website_out_of_stock_without_sellable_batch(): void
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 0, 150.00);

        $svc = app(PricingService::class);

        $this->assertFalse($svc->isWebsiteSellable($product));
        $this->assertSame(0, $svc->sellableStock($product, 'website'));
    }

    /**
     * D3 — pos_enabled = false disables a batch for BOTH web and POS: even a
     * batch flagged active-for-website must NOT be shown/priced on the website.
     */
    public function test_pos_disabled_batch_not_used_by_website(): void
    {
        $product = $this->makeProduct();
        $batch = $this->makeBatch($product, 5, 150.00, true);
        $batch->update(['pos_enabled' => false]); // flag still active, but disabled

        $svc = app(PricingService::class);

        $this->assertNull($svc->activeWebsiteBatch($product));
        $this->assertFalse($svc->isWebsiteSellable($product));
        $this->assertSame(0.0, $svc->price($product, null, null, 'website'));
        $this->assertSame(0, $svc->posBatches($product)->count());
    }

    public function test_fifo_allocation_spans_batches(): void
    {
        $product = $this->makeProduct();
        $b1 = $this->makeBatch($product, 3, 150.00, true); // oldest, 3 units
        $b2 = $this->makeBatch($product, 10, 190.00);      // 10 units

        $svc = app(PricingService::class);
        $alloc = $svc->websiteAllocation($product, 8);

        $this->assertCount(2, $alloc);
        $this->assertSame($b1->id, $alloc[0]['batch']->id);
        $this->assertSame(3, $alloc[0]['qty']);
        $this->assertSame($b2->id, $alloc[1]['batch']->id);
        $this->assertSame(5, $alloc[1]['qty']);
    }

    public function test_auto_advance_when_active_batch_depleted(): void
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 0, 150.00, true); // active but depleted
        $b2 = $this->makeBatch($product, 10, 190.00);

        $svc = app(PricingService::class);
        $next = $svc->advanceActiveBatchIfDepleted($product);

        $this->assertNotNull($next);
        $this->assertSame($b2->id, $next->id);
        $this->assertSame($b2->id, $svc->activeWebsiteBatch($product)?->id);
    }

    public function test_manual_override_respected_until_depleted(): void
    {
        $product = $this->makeProduct();
        $b1 = $this->makeBatch($product, 5, 150.00, true);
        $b2 = $this->makeBatch($product, 10, 190.00);

        $svc = app(PricingService::class);
        // Admin manually pins the newer batch — auto-advance must NOT move it
        $svc->setActiveWebsiteBatch($product, $b2->id);

        $this->assertSame($b2->id, $svc->activeWebsiteBatch($product)?->id);
        $this->assertSame($b2->id, $svc->advanceActiveBatchIfDepleted($product)?->id ?? $b2->id);
        $this->assertSame($b2->id, $svc->activeWebsiteBatch($product)?->id);
    }

    public function test_variant_price_comes_from_active_batch(): void
    {
        $product = $this->makeProduct();
        $batch   = $this->makeBatch($product, 5, 150.00, true);
        $vp      = ProductVariantPrice::create([
            'product_id' => $product->id,
            'price'      => 120,
            'stock'      => 5,
        ]);
        BatchVariantPrice::create([
            'stock_batch_id'   => $batch->id,
            'variant_price_id' => $vp->id,
            'price'            => 175.00,
            'stock'            => 5,
        ]);

        $svc = app(PricingService::class);

        $this->assertEquals(175.00, $svc->price($product, null, $vp->id, 'website'));
    }

    public function test_wholesale_tier_from_batch(): void
    {
        $product = $this->makeProduct(['is_wholesale' => true]);
        $batch   = $this->makeBatch($product, 50, 150.00, true);
        BatchWholesalePrice::create([
            'stock_batch_id'   => $batch->id,
            'variant_price_id' => null,
            'min_quantity'     => 10,
            'max_quantity'     => null,
            'wholesale_price'  => 15.00,
        ]);

        $svc = app(PricingService::class);

        $this->assertEquals(15.00, $svc->wholesale($product, 12, null, null));
        $this->assertEquals(0.00, $svc->wholesale($product, 5, null, null));
    }

    public function test_warranty_override_from_batch(): void
    {
        $product = $this->makeProduct();
        $batch   = $this->makeBatch($product, 5, 150.00, true);
        $tier    = ProductWarrantyTier::create([
            'product_id'      => $product->id,
            'tier_name'       => '1 Year',
            'warranty_days'   => 365,
            'additional_cost' => 50,
            'is_active'       => true,
        ]);
        BatchWarrantyTier::create([
            'stock_batch_id'   => $batch->id,
            'variant_price_id' => null,
            'warranty_tier_id' => $tier->id,
            'additional_cost'  => 40,
            'is_active'        => true,
        ]);

        $svc = app(PricingService::class);

        $this->assertEquals(40.00, $svc->warrantyAdjustment($product, $tier->id, null, null));
    }

    // ─────────────────────────────────────────────────────────────
    // 🆕 Storefront display: eligible batches & price range (Spec T3/T4)
    // ─────────────────────────────────────────────────────────────

    public function test_price_range_reflects_all_sellable_batches(): void
    {
        $product = $this->makeProduct();
        $b1 = $this->makeBatch($product, 5, 10.00);
        $b2 = $this->makeBatch($product, 30, 12.00);
        $b3 = $this->makeBatch($product, 15, 12.00);
        $b1->update(['mrp' => 20]);
        $b2->update(['mrp' => 22]);
        $b3->update(['mrp' => 24]);

        $svc = app(PricingService::class);
        $range = $svc->priceRange($product);

        $this->assertSame(10.0, $range['min_sale']);   // sale 10 - 12
        $this->assertSame(12.0, $range['max_sale']);
        $this->assertSame(20.0, $range['min_mrp']);    // mrp 20 - 24
        $this->assertSame(24.0, $range['max_mrp']);
        $this->assertSame(3, $range['count']);
    }

    public function test_price_range_excludes_exhausted_batch(): void // Spec T4
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 0, 10.00);   // exhausted → NOT shown
        $this->makeBatch($product, 30, 12.00);  // sellable

        $svc = app(PricingService::class);
        $range = $svc->priceRange($product);

        $this->assertSame(12.0, $range['min_sale']);
        $this->assertSame(12.0, $range['max_sale']);
        $this->assertSame(1, $range['count']);
    }

    public function test_price_range_returns_zero_when_no_sellable_batch(): void
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 0, 10.00);

        $svc = app(PricingService::class);
        $range = $svc->priceRange($product);

        $this->assertSame(0.0, $range['max_sale']);
        $this->assertSame(0, $range['count']);
    }

    // ─────────────────────────────────────────────────────────────
    // 🆕 Allocation engine — FIFO / LIFO / AVG (Spec §5–§8)
    // ─────────────────────────────────────────────────────────────

    public function test_fifo_allocate_prices_each_batch_portion(): void // Spec T1
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 3, 10.00);
        $this->makeBatch($product, 30, 12.00);

        $svc = app(PricingService::class);
        $alloc = $svc->allocate($product, 20, null, 'FIFO');

        $this->assertCount(2, $alloc);
        $this->assertSame(3, $alloc[0]['qty']);
        $this->assertEquals(10.0, $alloc[0]['unit_price']);
        $this->assertSame(17, $alloc[1]['qty']);
        $this->assertEquals(12.0, $alloc[1]['unit_price']);

        $total = array_sum(array_map(fn ($p) => $p['qty'] * $p['unit_price'], $alloc));
        $this->assertEquals(234.0, $total); // 3×10 + 17×12
    }

    public function test_lifo_allocate_consumes_newest_batch_first(): void // Spec §7
    {
        $product = $this->makeProduct();
        $b1 = $this->makeBatch($product, 3, 10.00);
        $b2 = $this->makeBatch($product, 30, 12.00);
        $b3 = $this->makeBatch($product, 15, 14.00);
        $b1->update(['mfg_date' => '2026-01-01']);
        $b2->update(['mfg_date' => '2026-02-01']);
        $b3->update(['mfg_date' => '2026-03-01']);

        $svc = app(PricingService::class);
        $alloc = $svc->allocate($product, 20, null, 'LIFO');

        $this->assertCount(2, $alloc);
        $this->assertSame($b3->id, $alloc[0]['batch']->id); // newest first
        $this->assertSame(15, $alloc[0]['qty']);
        $this->assertSame($b2->id, $alloc[1]['batch']->id);
        $this->assertSame(5, $alloc[1]['qty']);
    }

    public function test_avg_allocate_prices_at_weighted_average(): void // Spec §8
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 3, 10.00);
        $this->makeBatch($product, 17, 12.00);

        $svc = app(PricingService::class);
        $alloc = $svc->allocate($product, 20, null, 'AVG');

        // weighted = (3×10 + 17×12) / 20 = 11.70 applied to the whole quantity
        $this->assertNotEmpty($alloc);
        $this->assertEqualsWithDelta(11.7, $alloc[0]['unit_price'], 0.001);

        $total = array_sum(array_map(fn ($p) => $p['qty'] * $p['unit_price'], $alloc));
        $this->assertEqualsWithDelta(234.0, $total, 0.01);
    }

    public function test_allocate_uses_product_allocation_method_by_default(): void
    {
        $product = $this->makeProduct(['allocation_method' => 'LIFO']);
        $b1 = $this->makeBatch($product, 3, 10.00);
        $b2 = $this->makeBatch($product, 30, 12.00);
        $b1->update(['mfg_date' => '2026-01-01']);
        $b2->update(['mfg_date' => '2026-02-01']);

        $svc = app(PricingService::class);

        $this->assertSame('LIFO', $svc->allocationMethod($product));
        $alloc = $svc->allocate($product, 20); // no method → product default LIFO
        $this->assertSame($b2->id, $alloc[0]['batch']->id);
    }

    // ─────────────────────────────────────────────────────────────
    // 🆕 Variant applicability — Specific vs ALL batch (Spec T5/T6/T7)
    // ─────────────────────────────────────────────────────────────

    public function test_all_variant_batch_is_fallback_inventory(): void // Spec T6
    {
        $product = $this->makeProduct();
        $vp = ProductVariantPrice::create(['product_id' => $product->id, 'price' => 14, 'stock' => 30]);
        $all = StockBatch::create([
            'product_id'      => $product->id,
            'quantity'        => 30,
            'remaining_qty'   => 30,
            'selling_price'   => 14.0,
            'mrp'             => 20.0,
            'pos_enabled'     => true,
            'auto_advance'    => true,
            'is_all_variants' => true,
        ]);

        $svc = app(PricingService::class);
        $range = $svc->priceRange($product, $vp->id);

        $this->assertSame(14.0, $range['min_sale']);
        $this->assertSame(20.0, $range['min_mrp']);

        $alloc = $svc->allocate($product, 20, $vp->id, 'FIFO');
        $this->assertCount(1, $alloc);
        $this->assertSame($all->id, $alloc[0]['batch']->id);
        $this->assertSame(20, $alloc[0]['qty']);
    }

    public function test_specific_variant_batch_prioritized_over_all_batch(): void // Spec T7
    {
        $product = $this->makeProduct();
        $vp = ProductVariantPrice::create(['product_id' => $product->id, 'price' => 12, 'stock' => 5]);
        $specific = StockBatch::create([
            'product_id'       => $product->id,
            'quantity'         => 3,
            'remaining_qty'    => 3,
            'selling_price'    => 12.0,
            'pos_enabled'      => true,
            'auto_advance'     => true,
            'variant_price_id' => $vp->id,
        ]);
        $all = StockBatch::create([
            'product_id'      => $product->id,
            'quantity'        => 30,
            'remaining_qty'   => 30,
            'selling_price'   => 14.0,
            'pos_enabled'     => true,
            'auto_advance'    => true,
            'is_all_variants' => true,
        ]);

        $svc = app(PricingService::class);
        $alloc = $svc->allocate($product, 20, $vp->id, 'FIFO');

        $this->assertCount(2, $alloc);
        $this->assertSame($specific->id, $alloc[0]['batch']->id); // specific first
        $this->assertSame(3, $alloc[0]['qty']);
        $this->assertEquals(12.0, $alloc[0]['unit_price']);
        $this->assertSame($all->id, $alloc[1]['batch']->id);      // ALL fallback
        $this->assertSame(17, $alloc[1]['qty']);
        $this->assertEquals(14.0, $alloc[1]['unit_price']);

        $total = array_sum(array_map(fn ($p) => $p['qty'] * $p['unit_price'], $alloc));
        $this->assertEquals(274.0, $total); // 3×12 + 17×14
    }

    public function test_variant_with_no_batch_is_out_of_stock(): void // Spec T5
    {
        $product = $this->makeProduct();
        $vp = ProductVariantPrice::create(['product_id' => $product->id, 'price' => 12, 'stock' => 5]);
        $other = ProductVariantPrice::create(['product_id' => $product->id, 'price' => 99, 'stock' => 5]);
        // Batch scoped to ANOTHER variant only → Blue-M has no stock.
        StockBatch::create([
            'product_id'       => $product->id,
            'quantity'         => 3,
            'remaining_qty'    => 3,
            'selling_price'    => 12.0,
            'pos_enabled'      => true,
            'auto_advance'     => true,
            'variant_price_id' => $other->id,
        ]);

        $svc = app(PricingService::class);
        $range = $svc->priceRange($product, $vp->id);

        $this->assertSame(0.0, $range['max_sale']);
        $this->assertSame(0, $range['count']);
        $this->assertEmpty($svc->allocate($product, 5, $vp->id, 'FIFO'));
    }

    public function test_attach_catalog_ranges_sets_price_range_on_products(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $this->makeBatch($p1, 3, 10.00);
        $this->makeBatch($p1, 30, 12.00);
        $this->makeBatch($p2, 5, 15.00);

        $svc = app(PricingService::class);
        $svc->attachCatalogRanges(collect([$p1, $p2]));

        $this->assertSame(10.0, (float) $p1->price_min);
        $this->assertSame(12.0, (float) $p1->price_max);
        $this->assertFalse((bool) $p1->price_single);
        $this->assertSame(33, (int) $p1->stock_sellable);

        $this->assertSame(15.0, (float) $p2->price_min);
        $this->assertSame(15.0, (float) $p2->price_max);
        $this->assertTrue((bool) $p2->price_single);
    }

    public function test_catalog_min_sale_join_orders_by_lowest_batch_price(): void
    {
        // Mirrors FrontendController::joinBatchMinSale() (Phase 2.2/2.3).
        $pA = $this->makeProduct(); // lowest sellable batch = 10
        $pB = $this->makeProduct(); // lowest sellable batch = 15
        $pC = $this->makeProduct(); // no batches → stays visible (NULL min_sale)
        $this->makeBatch($pA, 3, 10.00);
        $this->makeBatch($pA, 30, 12.00);
        $this->makeBatch($pB, 5, 15.00);

        $sub = StockBatch::query()
            ->selectRaw('stock_batches.product_id AS product_id')
            ->selectRaw('MIN(stock_batches.selling_price) AS min_sale')
            ->where('stock_batches.pos_enabled', true)
            ->where('stock_batches.remaining_qty', '>', 0)
            ->where('stock_batches.selling_price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('stock_batches.exp_date')
                  ->orWhere('stock_batches.exp_date', '>=', now()->toDateString());
            })
            ->groupBy('stock_batches.product_id');

        $ids = Product::query()
            ->leftJoinSub($sub, 'bm', function ($join) {
                $join->on('products.id', '=', 'bm.product_id');
            })
            ->orderBy('bm.min_sale', 'asc')
            ->pluck('products.id')
            ->all();

        $this->assertCount(3, $ids);
        $this->assertContains($pC->id, $ids); // no-batch product not dropped
        $this->assertLessThan(array_search($pB->id, $ids), array_search($pA->id, $ids)); // 10 before 15
    }

    public function test_weighted_allocation_unit_preserves_per_batch_total(): void // Spec T1 money-exact
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 3, 10.00);
        $this->makeBatch($product, 30, 12.00);

        $svc = app(PricingService::class);

        // 20 units → 3×10 + 17×12 = 234 → weighted unit 11.70 → total 20 × 11.70 = 234
        $unit = $svc->weightedAllocationUnit($product, 20, null, 'FIFO');
        $this->assertEqualsWithDelta(11.7, $unit, 0.001);
        $this->assertEqualsWithDelta(234.0, 20 * $unit, 0.01);

        // Single-batch consumption (≤3) → exact batch unit (10)
        $this->assertEquals(10.0, $svc->weightedAllocationUnit($product, 3, null, 'FIFO'));
    }

    public function test_weighted_allocation_unit_returns_zero_when_underallocated(): void
    {
        $product = $this->makeProduct();
        $this->makeBatch($product, 3, 10.00); // only 3 available

        $svc = app(PricingService::class);

        // Requesting 5 but only 3 allocatable → 0 → caller falls back to active batch
        $this->assertSame(0.0, $svc->weightedAllocationUnit($product, 5, null, 'FIFO'));
    }

    public function test_specific_batch_does_not_serve_other_variants_via_bvp_rows(): void
    {
        // Spec §16/§26 — a batch explicitly bought for one variant (variant_price_id set,
        // is_all_variants=false) must NOT become available to other variants just because
        // it has a (legacy/stale) batch_variant_prices row listing them.
        $product = $this->makeProduct();
        $vpOwned = ProductVariantPrice::create(['product_id' => $product->id, 'price' => 12, 'stock' => 5]);
        $vpOther = ProductVariantPrice::create(['product_id' => $product->id, 'price' => 12, 'stock' => 0]);
        $batch = StockBatch::create([
            'product_id'       => $product->id,
            'quantity'         => 5,
            'remaining_qty'    => 5,
            'selling_price'    => 12.0,
            'pos_enabled'      => true,
            'auto_advance'     => true,
            'variant_price_id' => $vpOwned->id,
        ]);
        // Legacy bvp row listing the OTHER variant on this SPECIFIC batch
        BatchVariantPrice::create([
            'stock_batch_id'   => $batch->id,
            'variant_price_id' => $vpOther->id,
            'price'            => 0,
            'stock'            => 0,
        ]);

        $svc = app(PricingService::class);

        // Owned variant keeps its stock
        $this->assertSame(5, (int) $svc->eligibleBatches($product, $vpOwned->id)->sum('remaining_qty'));
        // Other variant must NOT inherit stock from the specific batch via its bvp row
        $this->assertSame(0, (int) $svc->eligibleBatches($product, $vpOther->id)->sum('remaining_qty'));
        $range = $svc->priceRange($product, $vpOther->id);
        $this->assertSame(0.0, $range['max_sale']);
    }
}
