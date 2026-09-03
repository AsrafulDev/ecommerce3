<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\WarrantySale;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3.2 (UPDATE-PLAN) — OrderStatusService is the ONE shared status→stock
 * engine. Entering an active status deducts stock (batch + COGS); leaving for
 * CANCELLED or RETURNED restocks via stockIn sale_return.
 */
class OrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);
    }

    protected function makeOrder(OrderStatus $status, int $qty = 2): array
    {
        $product = Product::create([
            'name'           => 'OSS Product',
            'slug'           => 'oss-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'OSS-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 10, 'unit_cost' => 100]);

        $customer = Customer::create([
            'name'     => 'OSS Customer',
            'slug'     => 'oss-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        $order = Order::create([
            'invoice_id'      => 'INV-OSS-'.uniqid(),
            'amount'          => 200 * $qty,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => $status->value,
        ]);
        OrderDetails::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 200,
            'qty'            => $qty,
        ]);

        return [$order, $product];
    }

    public function test_entering_active_status_deducts_stock_with_cogs(): void
    {
        [$order, $product] = $this->makeOrder(OrderStatus::PENDING);

        app(OrderStatusService::class)->handleStatusChange($order, OrderStatus::PENDING->value, OrderStatus::PACKED->value);

        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(8, (int) StockBatch::where('product_id', $product->id)->where('type', 'in')->sum('remaining_qty'));
        $this->assertNotNull(OrderDetails::where('order_id', $order->id)->first()->cogs);
    }

    public function test_cancelled_restocks_via_sale_return(): void
    {
        [$order, $product] = $this->makeOrder(OrderStatus::PACKED);
        app(StockManagementService::class)->stockOut($product, 2, ['type' => 'sale', 'id' => $order->id]); // already active
        $this->assertSame(8, (int) $product->fresh()->stock);

        app(OrderStatusService::class)->handleStatusChange($order, OrderStatus::PACKED->value, OrderStatus::CANCELLED->value);

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count());
    }

    public function test_returned_restocks_via_sale_return(): void
    {
        [$order, $product] = $this->makeOrder(OrderStatus::PACKED);
        app(StockManagementService::class)->stockOut($product, 2, ['type' => 'sale', 'id' => $order->id]);
        $this->assertSame(8, (int) $product->fresh()->stock);

        // Phase 3.2 — RETURNED now restocks exactly like CANCELLED.
        app(OrderStatusService::class)->handleStatusChange($order, OrderStatus::PACKED->value, OrderStatus::RETURNED->value);

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count());
    }

    public function test_sn_moves_to_sold_on_sale_and_back_on_cancel(): void
    {
        $product = Product::create([
            'name'           => 'SN Product',
            'slug'           => 'sn-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'SN-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);

        // Stock in 3 units with serial numbers A/B/C.
        $svc = app(StockManagementService::class);
        $svc->stockIn($product, [
            'quantity'       => 3,
            'unit_cost'      => 100,
            'serial_numbers' => ['SN-A', 'SN-B', 'SN-C'],
        ]);
        $batch = StockBatch::where('product_id', $product->id)->first();
        $this->assertSame(['SN-A', 'SN-B', 'SN-C'], $batch->sn_stock);

        $customer = Customer::create([
            'name'     => 'SN Customer',
            'slug'     => 'sn-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        // Pending order with ONE unit + a WarrantySale carrying serial SN-A.
        $order = Order::create([
            'invoice_id'      => 'INV-SN-'.uniqid(),
            'amount'          => 200,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => OrderStatus::PENDING->value,
        ]);
        $detail = OrderDetails::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 200,
            'qty'            => 1,
        ]);
        WarrantySale::create([
            'order_detail_id' => $detail->id,
            'order_id'        => $order->id,
            'product_id'      => $product->id,
            'customer_id'     => $customer->id,
            'serial_numbers'  => ['SN-A'],
            'stock_batch_id'  => $batch->id,
            'warranty_type'   => 'none',
            'status'          => 'active',
        ]);

        // Sell (enter active): stockOut 1 from the batch AND SN-A → sn_sold.
        app(OrderStatusService::class)->handleStatusChange($order, OrderStatus::PENDING->value, OrderStatus::PACKED->value);

        $batch->refresh();
        $this->assertSame(2, (int) $batch->remaining_qty);
        $this->assertSame(['SN-B', 'SN-C'], $batch->sn_stock);
        $this->assertSame(['SN-A'], $batch->sn_sold);

        // Cancel: stock restored AND SN-A back in sn_stock.
        app(OrderStatusService::class)->handleStatusChange($order, OrderStatus::PACKED->value, OrderStatus::CANCELLED->value);

        $batch->refresh();
        $this->assertSame(['SN-A', 'SN-B', 'SN-C'], $batch->sn_stock);
        $this->assertSame([], $batch->sn_sold);
    }
}
