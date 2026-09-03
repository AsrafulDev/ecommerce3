<?php

namespace Tests\Feature;

use App\Models\Courierapi;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 3.1 (UPDATE-PLAN) — courier:check-status must select enum statuses,
 * transition via Order::transitionTo (enum + order_note) and run the shared
 * stock engine. No legacy int writes / no raw products.stock mutations.
 */
class CourierStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);
    }

    protected function makePackedOrder(string $tracking): array
    {
        $product = Product::create([
            'name'           => 'Courier Product',
            'slug'           => 'courier-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'COU-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 10, 'unit_cost' => 100]);

        $customer = Customer::create([
            'name'     => 'Courier Customer',
            'slug'     => 'courier-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        $order = Order::create([
            'invoice_id'          => 'INV-COU-'.uniqid(),
            'amount'              => 400,
            'discount'            => 0,
            'shipping_charge'     => 0,
            'customer_id'         => $customer->id,
            'order_status'        => \App\Enums\OrderStatus::PACKED->value,
            'courier_type'        => 'pathao',
            'courier_tracking_id' => $tracking,
        ]);
        OrderDetails::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 200,
            'qty'            => 2,
        ]);

        // Stock consumed when the order was packed.
        app(StockManagementService::class)->stockOut($product, 2, ['type' => 'sale', 'id' => $order->id]);

        return [$order, $product];
    }

    public function test_courier_delivered_writes_enum_and_completes(): void
    {
        [$order, $product] = $this->makePackedOrder('PDX-DEL-1');

        Courierapi::create(['type' => 'pathao', 'status' => 1, 'token' => 'tok', 'url' => 'https://api.example.com/aladdin']);

        Http::fake(['*PDX-DEL-1/info*' => Http::response(['data' => ['order_status_slug' => 'delivered']])]);

        $this->artisan('courier:check-status', ['--force' => true])->assertExitCode(0);

        // Enum status written (no legacy int), stock stays consumed (completed = active).
        $this->assertSame(\App\Enums\OrderStatus::COMPLETED->value, $order->fresh()->order_status);
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertTrue($order->notes()->exists(), 'transitionTo should write an order note');
    }

    public function test_courier_cancelled_writes_enum_and_restocks(): void
    {
        [$order, $product] = $this->makePackedOrder('PDX-CAN-1');

        Courierapi::create(['type' => 'pathao', 'status' => 1, 'token' => 'tok', 'url' => 'https://api.example.com/aladdin']);

        Http::fake(['*PDX-CAN-1/info*' => Http::response(['data' => ['order_status_slug' => 'cancelled']])]);

        $this->assertSame(8, (int) $product->fresh()->stock);

        $this->artisan('courier:check-status', ['--force' => true])->assertExitCode(0);

        // Enum status + restock through the shared engine (sale_return batch).
        $this->assertSame(\App\Enums\OrderStatus::CANCELLED->value, $order->fresh()->order_status);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count());
    }
}
