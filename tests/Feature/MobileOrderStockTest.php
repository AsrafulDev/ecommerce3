<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\StockBatch;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 2.5 (UPDATE-PLAN) — the mobile (Flutter) order endpoint must deduct stock
 * through StockManagementService::stockOut (batch-tracked + COGS), never a raw
 * `products.stock = max(0, ...)` write.
 */
class MobileOrderStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);
    }

    public function test_mobile_order_deducts_stock_via_stock_service(): void
    {
        $customer = Customer::create([
            'name'     => 'Mobile Customer',
            'slug'     => 'mobile-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);
        Sanctum::actingAs($customer);

        $product = Product::create([
            'name'           => 'Mobile Product',
            'slug'           => 'mobile-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'MOB-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 10, 'unit_cost' => 100]);

        $shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);

        Cart::create([
            'customer_id' => $customer->id,
            'product_id'  => $product->id,
            'quantity'    => 2,
            'price'       => 200,
        ]);

        $response = $this->postJson('/api/v1/mobile/orders', [
            'name'           => 'Mobile Customer',
            'phone'          => $customer->phone,
            'address'        => 'Mirpur, Dhaka',
            'area'           => $shipping->id,
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(201)->assertJson(['status' => 'success']);

        // Order created with a batch-tracked detail (COGS + allocation stored).
        $order = Order::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $detail = OrderDetails::where('order_id', $order->id)->first();
        $this->assertNotNull($detail);
        $this->assertNotNull($detail->cogs, 'COGS should be captured from stockOut');
        $this->assertNotEmpty($detail->batch_ids, 'batch allocation should be stored');

        // Stock deducted exactly once via the batch source-of-truth.
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(8, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));

        // Cart cleared after the order.
        $this->assertSame(0, Cart::where('customer_id', $customer->id)->count());
    }
}
