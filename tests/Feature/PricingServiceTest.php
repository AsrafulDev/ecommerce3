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
}
