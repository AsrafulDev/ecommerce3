<?php

namespace Tests\Feature\TransactionReset;

use App\Models\Category;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\WarrantySale;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use App\Services\TransactionReset\SaleResetService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Serial numbers of a reset sale go back into the batch they were sold from, once, and only when that batch can be
 * established from the records. Nothing is assigned by guesswork.
 */
class SerialResetTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $admin;

    private StockBatch $older;

    private StockBatch $newer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $this->admin = User::first();
        $this->actingAs($this->admin, 'admin');
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $category = Category::create(['name' => 'SN', 'slug' => 'sn-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Serial item', 'slug' => 'sn-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'SN-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);

        // Two stock-in batches, the older one first, each with its own serial numbers.
        $stock = app(StockManagementService::class);
        $this->older = $stock->stockIn($this->product, ['quantity' => 3, 'unit_cost' => 100, 'serial_numbers' => ['A1', 'A2', 'A3']]);
        $this->newer = $stock->stockIn($this->product, ['quantity' => 1, 'unit_cost' => 120, 'serial_numbers' => ['B1']]);
    }

    /**
     * A completed sale of two units from the older batch, with the serials that left it recorded on a warranty sale.
     */
    private function soldWithSerials(array $serials, ?int $linkedBatchId, int $qty = 2): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-SN-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        $line = OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => $qty,
            'product_name' => 'Serial item', 'purchase_price' => 100,
        ]);
        $buyer = Customer::create([
            'name' => 'Serial buyer', 'slug' => 'sb-'.uniqid(), 'phone' => '01700000002',
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ]);
        WarrantySale::create([
            'order_id' => $order->id, 'order_detail_id' => $line->id, 'customer_id' => $buyer->id,
            'product_id' => $this->product->id, 'serial_numbers' => $serials, 'warranty_type' => 'serial',
            'stock_batch_id' => $linkedBatchId,
        ]);

        app(OrderStatusService::class)->handleStatusChange($order, 'pending', 'completed');
        $order->update(['order_status' => 'completed']);

        return $order->fresh();
    }

    private function reverse(Order $order): array
    {
        return app(SaleResetService::class)->reverse($order, $this->admin, 'Serial reset test.');
    }

    public function test_sold_serials_return_to_the_batch_they_came_from(): void
    {
        $order = $this->soldWithSerials(['A1', 'A2'], $this->older->id);
        $this->assertSame(['A3'], $this->older->fresh()->sn_stock, 'the sold serials left the older batch');

        $result = $this->reverse($order);

        $older = $this->older->fresh();
        $this->assertEqualsCanonicalizing(['A1', 'A2', 'A3'], $older->sn_stock);
        $this->assertSame([], $older->sn_sold, 'nothing is still recorded as sold from the older batch');
        $this->assertSame(['B1'], $this->newer->fresh()->sn_stock, 'the newer batch was not touched');
        $this->assertSame(['A1' => $this->older->id, 'A2' => $this->older->id], $result['serials']);
    }

    public function test_a_serial_is_available_only_once_after_the_reset(): void
    {
        $order = $this->soldWithSerials(['A1', 'A2'], $this->older->id);

        $this->reverse($order);

        $counts = array_count_values($this->older->fresh()->sn_stock);
        $this->assertSame(1, $counts['A1']);
        $this->assertSame(1, $counts['A2']);
    }

    public function test_a_repeated_reset_cannot_restore_the_same_serial_twice(): void
    {
        $order = $this->soldWithSerials(['A1', 'A2'], $this->older->id);
        $this->reverse($order);

        try {
            $this->reverse($order->fresh());
            $this->fail('a second reset must be refused');
        } catch (\DomainException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertSame(1, array_count_values($this->older->fresh()->sn_stock)['A1']);
    }

    public function test_a_serial_never_recorded_as_sold_blocks_the_reset(): void
    {
        $order = $this->soldWithSerials(['A1', 'A2'], $this->older->id);
        // The sale's recorded serials are changed after completion: the reset must not trust them.
        WarrantySale::where('order_id', $order->id)->update(['serial_numbers' => ['A1', 'ZZ9']]);
        $before = $this->older->fresh()->sn_stock;

        try {
            $this->reverse($order);
            $this->fail('an unknown serial must block the reset');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('ZZ9', $e->getMessage());
        }

        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertSame($before, $this->older->fresh()->sn_stock);
    }

    public function test_a_serial_recorded_as_sold_from_two_batches_blocks_the_reset(): void
    {
        $order = $this->soldWithSerials(['A1', 'A2'], $this->older->id);
        $this->newer->update(['sn_sold' => ['A1']]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('more than one batch');
        $this->reverse($order);
    }

    public function test_a_sale_whose_link_disagrees_with_its_serials_blocks_the_reset(): void
    {
        $order = $this->soldWithSerials(['A1', 'A2'], $this->older->id);
        WarrantySale::where('order_id', $order->id)->update(['stock_batch_id' => $this->newer->id]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('linked to batch');
        $this->reverse($order);
    }
}
