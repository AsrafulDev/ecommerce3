<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PurchaseController;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Phase 2.6 (UPDATE-PLAN) — PurchaseController::returnItem() must route the
 * return through StockManagementService::stockOut with reference_type
 * `purchase_return` (batch FIFO + COGS + trace row), never manual
 * remaining_qty/products.stock decrements.
 */
class PurchaseReturnStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    public function test_return_item_uses_stock_service_purchase_return_reference(): void
    {
        $supplier = Supplier::create(['name' => 'Return Supplier']);
        $product = Product::create([
            'name'           => 'Purchase Return Product',
            'slug'           => 'purchase-return-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'PRET-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);

        $purchase = Purchase::create([
            'supplier_id'   => $supplier->id,
            'invoice_no'    => 'INV-PRET-'.uniqid(),
            'purchase_date' => now()->toDateString(),
            'total_qty'     => 10,
            'subtotal'      => 1000,
            'discount'      => 0,
            'shipping_cost' => 0,
            'grand_total'   => 1000,
            'paid_amount'   => 0,
            'due_amount'    => 1000,
            'status'        => 1,
            'created_by'    => \App\Models\User::first()->id,
        ]);

        app(StockManagementService::class)->stockIn($product, [
            'quantity'     => 10,
            'unit_cost'    => 100,
            'purchase_id'  => $purchase->id,
            'supplier_id'  => $supplier->id,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);

        $item = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id'  => $product->id,
            'qty'         => 10,
            'unit_cost'   => 100,
            'line_total'  => 1000,
        ]);

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(10, (int) StockBatch::where('purchase_id', $purchase->id)->sum('remaining_qty'));

        // Return 3 via the controller.
        app(PurchaseController::class)->returnItem(new Request(['return_qty' => 3]), $item->id);

        // returned_qty recorded…
        $this->assertSame(3, (int) $item->fresh()->returned_qty);

        // …and stock moved through the service with the purchase_return reference.
        $this->assertSame(7, (int) $product->fresh()->stock);
        $this->assertSame(7, (int) StockBatch::where('type', 'in')->where('purchase_id', $purchase->id)->sum('remaining_qty'));
        $this->assertSame(1, StockBatch::where('type', 'out')
            ->where('reference_type', 'purchase_return')
            ->where('reference_id', $purchase->id)
            ->count());

        // Denormalized copy still matches the batch source of truth.
        $this->assertSame(
            (int) StockBatch::where('product_id', $product->id)->where('type', 'in')->sum('remaining_qty'),
            (int) $product->fresh()->stock
        );
    }
}
