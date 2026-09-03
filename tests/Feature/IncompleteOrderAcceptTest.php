<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2.2 (UPDATE-PLAN) — converting an incomplete order to a real order must
 * deduct stock through StockManagementService::stockOut() (batch-tracked + COGS),
 * never through a raw `products.stock = max(0, ...)` write.
 */
class IncompleteOrderAcceptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    public function test_accept_converts_order_via_stock_service_batch_aware(): void
    {
        $product = Product::create([
            'name'           => 'Incomplete Product',
            'slug'           => 'incomplete-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'INC-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 10, 'unit_cost' => 100]);

        $phone = '017'.random_int(10000000, 99999999);
        $incomplete = IncompleteOrder::create([
            'name'         => 'Iqbal Hossain',
            'phone'        => $phone,
            'address'      => 'Mirpur, Dhaka',
            'items'        => [
                ['id' => $product->id, 'qty' => 3, 'price' => 200, 'name' => $product->name],
            ],
            'total_amount' => 600,
        ]);

        $response = $this->post(route('admin.incomplete-orders.accept', $incomplete->id));
        $response->assertStatus(302);

        // Customer + order + detail created.
        $customer = Customer::where('phone', $phone)->first();
        $this->assertNotNull($customer);
        $order = Order::where('customer_id', $customer->id)->first();
        $this->assertNotNull($order);

        $detail = OrderDetails::where('order_id', $order->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame(3, (int) $detail->qty);
        $this->assertNotNull($detail->cogs, 'COGS should be captured from stockOut');
        $this->assertNotEmpty($detail->batch_ids, 'batch allocation should be stored on the detail');

        // Stock deducted via the batch source-of-truth — no drift.
        $this->assertSame(7, (int) $product->fresh()->stock);
        $this->assertSame(7, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));

        // Incomplete order consumed.
        $this->assertDatabaseMissing('incomplete_orders', ['id' => $incomplete->id]);
    }
}
