<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\StockManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * StockManagementService — the single source of truth for stock movement.
 * Every stock-in creates a StockBatch and reconciles `products.stock`.
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // These tests assert against the denormalized `products.stock` column
        // (legacy path). Keep batch-wise pricing OFF so the `Product::stock`
        // accessor doesn't mask the raw column with `website_stock`.
        config(['pricing.batch_wise' => false]);
    }

    protected function makeProduct(): Product
    {
        return Product::create([
            'name'           => 'Stock Product',
            'slug'           => 'stock-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'ST-'.uniqid(),
            'purchase_price' => 50,
            'new_price'      => 100,
            'stock'          => 0,
            'status'         => 1,
        ]);
    }

    public function test_stock_in_creates_batch_and_syncs_product_stock(): void
    {
        $product = $this->makeProduct();
        $service = app(StockManagementService::class);

        $batch = $service->stockIn($product, [
            'quantity'      => 25,
            'unit_cost'     => 40,
            'batch_no'      => 'B-TEST-1',
            'selling_price' => 100,
        ]);

        $this->assertNotNull($batch->id);
        $this->assertSame(25, $batch->remaining_qty);
        $this->assertSame(25, (int) $product->fresh()->stock);

        // Reconcile from batches keeps the denormalized copy correct.
        $service->syncStockFromBatches($product->id);
        $this->assertSame(25, (int) $product->fresh()->stock);
    }

    public function test_stock_in_rejects_zero_quantity(): void
    {
        $product = $this->makeProduct();

        $this->expectException(\InvalidArgumentException::class);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 0]);
    }

    public function test_sync_stock_from_batches_does_not_zero_products_without_batches(): void
    {
        $product = $this->makeProduct();
        // Simulate legacy stock value with no batches — must be left untouched.
        $product->update(['stock' => 7]);

        app(StockManagementService::class)->syncStockFromBatches($product->id);

        $this->assertSame(7, (int) $product->fresh()->stock);
    }
}
