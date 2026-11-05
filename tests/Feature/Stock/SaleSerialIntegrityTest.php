<?php

namespace Tests\Feature\Stock;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\WarrantySale;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Serial-tracked sale lines resolve to their origin batch, every serial is allocated exactly once, and a failed
 * completion leaves the order, its stock and its serials exactly as they were.
 */
class SaleSerialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private StockBatch $batchA;

    private StockBatch $batchB;

    private int $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $this->buyer = \App\Models\Customer::create([
            'name' => 'Serial buyer', 'slug' => 'si-buyer-'.uniqid(), 'phone' => '01700000003',
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;

        $category = Category::create(['name' => 'SI', 'slug' => 'si-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Serial item', 'slug' => 'si-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'SI-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);

        $stock = app(StockManagementService::class);
        $this->batchA = $stock->stockIn($this->product, ['quantity' => 3, 'unit_cost' => 100, 'serial_numbers' => ['A1', 'A2', 'A3']]);
        $this->batchB = $stock->stockIn($this->product, ['quantity' => 2, 'unit_cost' => 120, 'serial_numbers' => ['B1', 'B2']]);
    }

    /** A pending order with one serial-tracked line per entry: [qty, serials, linked batch id or null]. */
    private function order(array $lines): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-SI-'.uniqid(), 'amount' => 0, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->buyer, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        foreach ($lines as [$qty, $serials, $link]) {
            $line = OrderDetails::create([
                'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => $qty,
                'product_name' => 'Serial item', 'purchase_price' => 100,
            ]);
            WarrantySale::create([
                'order_id' => $order->id, 'order_detail_id' => $line->id, 'customer_id' => $this->buyer,
                'product_id' => $this->product->id, 'serial_numbers' => $serials, 'warranty_type' => 'serial',
                'stock_batch_id' => $link,
            ]);
        }

        return $order->fresh();
    }

    private function complete(Order $order): string
    {
        return app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::COMPLETED, 'Test completion.');
    }

    private function soldIn(StockBatch $batch): array
    {
        return $batch->fresh()->sn_sold ?? [];
    }

    public function test_a_serial_sold_from_its_linked_batch_is_moved_to_sold_there(): void
    {
        $order = $this->order([[2, ['A1', 'A2'], $this->batchA->id]]);

        $this->assertSame('applied', $this->complete($order));

        $this->assertEqualsCanonicalizing(['A1', 'A2'], $this->soldIn($this->batchA));
        $this->assertSame(['A3'], $this->batchA->fresh()->sn_stock);
        $this->assertSame(1, (int) $this->batchA->fresh()->remaining_qty);
        $this->assertSame(['B1', 'B2'], $this->batchB->fresh()->sn_stock, 'the other batch was not touched');
        $line = OrderDetails::where('order_id', $order->id)->firstOrFail();
        $this->assertNotNull($line->cogs);
        $this->assertSame($this->batchA->id, (int) $line->batch_ids[0]['batch_id']);
    }

    public function test_a_serial_from_another_batch_is_taken_from_its_real_batch_when_no_link_is_set(): void
    {
        $order = $this->order([[1, ['B1'], null]]);

        $this->assertSame('applied', $this->complete($order));

        $this->assertSame(['B1'], $this->soldIn($this->batchB));
        $this->assertSame([], $this->soldIn($this->batchA), 'nothing was taken from the batch that does not hold B1');
        $this->assertSame($this->batchB->id, (int) WarrantySale::where('order_id', $order->id)->value('stock_batch_id'));
    }

    public function test_a_link_to_the_wrong_batch_fails_and_changes_nothing(): void
    {
        $order = $this->order([[1, ['B1'], $this->batchA->id]]);

        try {
            $this->complete($order);
            $this->fail('a serial linked to the wrong batch must fail');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('linked to batch', $e->getMessage());
        }

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame(['B1', 'B2'], $this->batchB->fresh()->sn_stock);
    }

    public function test_an_unknown_serial_fails_the_completion(): void
    {
        $order = $this->order([[1, ['ZZ9'], null]]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('ZZ9');
        $this->complete($order);
    }

    public function test_a_duplicate_serial_on_one_line_fails_the_completion(): void
    {
        $order = $this->order([[2, ['A1', 'A1'], null]]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('more than once');
        $this->complete($order);
    }

    public function test_a_serial_already_sold_fails_the_completion(): void
    {
        $first = $this->order([[1, ['A1'], null]]);
        $this->assertSame('applied', $this->complete($first));

        $second = $this->order([[1, ['A1'], null]]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already sold');
        $this->complete($second);
    }

    public function test_multiple_serials_from_multiple_valid_batches_are_allocated_exactly_once_each(): void
    {
        $order = $this->order([[3, ['A1', 'A2', 'B1'], null]]);

        $this->assertSame('applied', $this->complete($order));

        $this->assertEqualsCanonicalizing(['A1', 'A2'], $this->soldIn($this->batchA));
        $this->assertEqualsCanonicalizing(['B1'], $this->soldIn($this->batchB));
        $this->assertSame(['A3'], $this->batchA->fresh()->sn_stock);
        $this->assertSame(['B2'], $this->batchB->fresh()->sn_stock);
        $this->assertSame(1, (int) $this->batchA->fresh()->remaining_qty);
        $this->assertSame(1, (int) $this->batchB->fresh()->remaining_qty);
    }

    public function test_a_failed_line_rolls_back_the_whole_sale_completion(): void
    {
        // Line 1 is valid and would be allocated first. Line 2 has an unknown serial, so the whole completion must undo line 1.
        $order = $this->order([[2, ['A1', 'A2'], null], [1, ['ZZ9'], null]]);
        $stockBefore = $this->product->fresh()->stock;

        try {
            $this->complete($order);
            $this->fail('the completion must fail');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('ZZ9', $e->getMessage());
        }

        $this->assertSame('pending', $order->fresh()->order_status, 'the status change was rolled back');
        $this->assertEqualsCanonicalizing(['A1', 'A2', 'A3'], $this->batchA->fresh()->sn_stock);
        $this->assertSame([], $this->soldIn($this->batchA));
        $this->assertSame(3, (int) $this->batchA->fresh()->remaining_qty);
        $this->assertSame($stockBefore, $this->product->fresh()->stock);
        $this->assertSame(0, StockBatch::where('reference_type', 'sale')->where('reference_id', $order->id)->count());
        foreach (OrderDetails::where('order_id', $order->id)->get() as $line) {
            $this->assertNull($line->cogs);
        }
    }

    public function test_retrying_a_failed_completion_moves_each_serial_and_each_unit_exactly_once(): void
    {
        $order = $this->order([[2, ['A1', 'A2'], null], [1, ['ZZ9'], null]]);
        try {
            $this->complete($order);
        } catch (\DomainException $e) {
            // expected: the unknown serial
        }

        // The unknown serial is corrected on the sale, then the completion is retried.
        WarrantySale::where('order_id', $order->id)->where('serial_numbers', 'like', '%ZZ9%')->update(['serial_numbers' => json_encode(['B1'])]);
        $this->assertSame('applied', $this->complete($order));

        $this->assertEqualsCanonicalizing(['A1', 'A2'], $this->soldIn($this->batchA));
        $this->assertEqualsCanonicalizing(['B1'], $this->soldIn($this->batchB));
        $this->assertSame(['A3'], $this->batchA->fresh()->sn_stock);
        $this->assertSame(1, (int) $this->batchA->fresh()->remaining_qty);
        $this->assertSame(1, (int) $this->batchB->fresh()->remaining_qty, 'B1 was the only unit taken from batch B');
        // One stock-out record per batch used: the 2 units from A, and the 1 unit from B.
        $this->assertSame(2, StockBatch::where('reference_type', 'sale')->where('reference_id', $order->id)->count());
    }

    public function test_confirming_an_order_with_an_unknown_serial_is_refused_and_rolled_back(): void
    {
        $order = $this->order([[2, ['A1', 'A2'], null], [1, ['ZZ9'], null]]);
        $admin = User::first();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.order.confirm'), ['order_id' => $order->id, 'note' => 'Confirm test.'])
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertEqualsCanonicalizing(['A1', 'A2', 'A3'], $this->batchA->fresh()->sn_stock);
        $this->assertSame(3, (int) $this->batchA->fresh()->remaining_qty);
    }

    public function test_a_serial_count_that_does_not_match_the_quantity_fails(): void
    {
        $order = $this->order([[2, ['A1'], null]]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('serial number');
        $this->complete($order);
    }
}
