<?php

namespace Tests\Feature;

use App\Models\DamageProduct;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Product delete behaviour:
 * - Product with any transaction (order / warranty / damage / stock batch) → soft delete.
 * - Product with no transactions → hard delete (permanent).
 *
 * See `Product::hasTransactions()` and `Admin\ProductController::destroy()`.
 */
class ProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class); // admin user + permissions
        $this->actingAs(\App\Models\User::first(), 'admin');
    }

    protected function makeProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'name'           => 'Delete Test Product',
            'slug'           => 'delete-test-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'DP-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 150,
            'stock'          => 10,
            'status'         => 1,
        ], $attrs));
    }

    public function test_product_with_order_transaction_is_soft_deleted(): void
    {
        $product = $this->makeProduct();
        OrderDetails::create([
            'order_id'       => 1,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 150,
            'qty'            => 1,
        ]);

        $this->post('/admin/products/destroy', ['hidden_id' => $product->id])
            ->assertRedirect();

        $this->assertNull(Product::find($product->id), 'soft-deleted product must be hidden from normal queries');
        $this->assertNotNull(Product::withTrashed()->find($product->id), 'soft-deleted product row must remain');
    }

    public function test_product_with_stock_batch_transaction_is_soft_deleted(): void
    {
        $product = $this->makeProduct();
        StockBatch::create([
            'product_id'    => $product->id,
            'quantity'      => 5,
            'remaining_qty' => 5,
            'unit_cost'     => 80,
            'type'          => 'in',
        ]);

        $this->post('/admin/products/destroy', ['hidden_id' => $product->id])
            ->assertRedirect();

        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }

    public function test_product_with_damage_record_is_soft_deleted(): void
    {
        $product = $this->makeProduct();
        DamageProduct::create(['product_id' => $product->id]);

        $this->post('/admin/products/destroy', ['hidden_id' => $product->id])
            ->assertRedirect();

        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }

    public function test_product_without_any_transaction_is_hard_deleted(): void
    {
        $product = $this->makeProduct();

        $this->post('/admin/products/destroy', ['hidden_id' => $product->id])
            ->assertRedirect();

        // Row is completely gone — even withTrashed() cannot find it.
        $this->assertNull(Product::withTrashed()->find($product->id));
    }
}
