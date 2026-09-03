<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2.7 (UPDATE-PLAN) — a supplier return (goods back into stock) must be
 * restocked through StockManagementService::stockIn with a `purchase_return`
 * reference — never raw products.stock / stock_batches.remaining_qty increments.
 */
class SupplierReturnStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    public function test_supplier_return_restocks_via_stock_service(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier Return Supplier']);
        $product = Product::create([
            'name'           => 'Supplier Return Product',
            'slug'           => 'supplier-return-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'SRET-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 5, 'unit_cost' => 100]);
        $this->assertSame(5, (int) $product->fresh()->stock);

        $response = $this->post(route('admin.stock.supplier-returns.store'), [
            'supplier_id' => $supplier->id,
            'return_date' => now()->toDateString(),
            'reason'      => 'Damaged in transit — returned to us',
            'items'       => [
                ['product_id' => $product->id, 'qty' => 3, 'unit_cost' => 50],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('supplier_returns', [
            'supplier_id' => $supplier->id,
            'total_qty'   => 3,
        ]);

        $return = SupplierReturn::latest('id')->first();

        // Restocked via stockIn → new 'in' batch with the purchase_return reference.
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(1, StockBatch::where('reference_type', 'purchase_return')
            ->where('reference_id', $return->id)
            ->where('remaining_qty', 3)
            ->count());

        // Denormalized copy matches the batch source of truth.
        $this->assertSame(
            (int) StockBatch::where('product_id', $product->id)->where('type', 'in')->sum('remaining_qty'),
            (int) $product->fresh()->stock
        );
    }
}
