<?php

namespace Tests\Feature\Orders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\WarrantySale;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The six legacy admin status paths (order change, bulk status, POS create, POS update, return steps and cancel)
 * change status and stock in one transaction on a locked order. Each test fails the change after the real stock
 * engine has already moved stock, so only a rollback can put the order and its stock back as they were.
 */
class LegacyStatusPathsTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private StockBatch $batch;

    private ShippingCharge $shipping;

    private int $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $admin = User::first();
        $admin->update(['password' => bcrypt('password')]);
        $this->actingAs($admin, 'admin');
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $this->buyer = Customer::create([
            'name' => 'Legacy buyer', 'slug' => 'lp-buyer-'.uniqid(), 'phone' => '01700000010',
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;

        $category = Category::create(['name' => 'Legacy', 'slug' => 'lp-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Legacy item', 'slug' => 'lp-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'LP-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        $this->batch = app(StockManagementService::class)->stockIn($this->product, ['quantity' => 20, 'unit_cost' => 100]);

        $this->shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
    }

    /** A pending order with one line of the given quantity, and its shipping row. Nothing has left stock yet. */
    private function pendingOrder(int $qty = 2): Order
    {
        $order = Order::create([
            'invoice_id' => 'LP-'.uniqid(), 'amount' => 200 * $qty + 60, 'discount' => 0, 'shipping_charge' => 60,
            'customer_id' => $this->buyer, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => $qty,
            'product_name' => 'Legacy item', 'purchase_price' => 100,
        ]);
        Shipping::create([
            'order_id' => $order->id, 'customer_id' => $this->buyer, 'name' => 'Legacy buyer',
            'phone' => '01700000010', 'address' => 'Dhaka', 'area' => 'Inside Dhaka',
        ]);

        return $order->fresh();
    }

    /** Runs the real status engine, then fails. Whatever it changed in stock must come back with the status. */
    private function failAfterStockMoves(?int $orderId = null, string $class = \DomainException::class): void
    {
        $this->app->instance(OrderStatusService::class, new class($orderId, $class) extends OrderStatusService {
            public function __construct(private ?int $orderId, private string $class)
            {
            }

            public function handleStatusChange(\App\Models\Order $order, $oldStatus, $newStatus, bool $strictCost = false): void
            {
                parent::handleStatusChange($order, $oldStatus, $newStatus, $strictCost);

                if ($this->orderId === null || $this->orderId === $order->id) {
                    throw new ($this->class)('Injected failure after the stock moved.');
                }
            }
        });
    }

    /** Units on hand across the product's batches. A restock is booked as a new sale-return batch, so one batch is not enough. */
    private function remaining(): int
    {
        return (int) StockBatch::where('product_id', $this->product->id)->sum('remaining_qty');
    }

    public function test_order_change_applies_the_status_and_its_stock_together(): void
    {
        $order = $this->pendingOrder(2);

        $this->post(route('admin.order_change'), $this->changePayload($order, 'confirmed'))->assertRedirect();

        $this->assertSame('confirmed', $order->fresh()->order_status);
        $this->assertSame(18, $this->remaining());
    }

    public function test_order_change_that_fails_after_its_stock_moved_changes_neither_status_nor_stock(): void
    {
        $order = $this->pendingOrder(2);
        $this->failAfterStockMoves();

        $this->post(route('admin.order_change'), $this->changePayload($order, 'confirmed'))->assertRedirect();

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame(20, $this->remaining());
    }

    public function test_bulk_status_refuses_only_the_order_that_failed(): void
    {
        $failing = $this->pendingOrder(2);
        $other = $this->pendingOrder(3);
        $this->failAfterStockMoves($failing->id);

        $this->getJson(route('admin.order.status', [
            'order_status' => 'confirmed',
            'order_ids'    => [$failing->id, $other->id],
        ]))
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);

        $this->assertSame('pending', $failing->fresh()->order_status);
        $this->assertSame('confirmed', $other->fresh()->order_status);
        $this->assertSame(17, $this->remaining());
    }

    public function test_pos_sale_that_fails_leaves_no_order_customer_or_stock_behind(): void
    {
        $this->seedPosCart(['serial_numbers' => []]);
        $this->failAfterStockMoves();
        $orders = Order::count();
        $details = OrderDetails::count();
        $phone = '01711000001';

        $this->post(route('admin.order.store'), $this->posPayload($phone))->assertRedirect();

        $this->assertSame($orders, Order::count());
        $this->assertSame($details, OrderDetails::count());
        $this->assertFalse(Customer::where('phone', $phone)->exists());
        $this->assertSame(20, $this->remaining());
        $this->assertGreaterThan(0, Cart::instance('pos_shopping')->count(), 'The cart is kept for a retry.');
    }

    public function test_pos_sale_with_a_serial_already_registered_is_refused_without_a_partial_order(): void
    {
        $prior = $this->pendingOrder(1);
        WarrantySale::create([
            'order_id' => $prior->id, 'order_detail_id' => OrderDetails::where('order_id', $prior->id)->value('id'),
            'customer_id' => $this->buyer, 'product_id' => $this->product->id, 'serial_numbers' => ['DUP-1'],
            'warranty_type' => 'serial', 'status' => 'active',
        ]);
        $this->seedPosCart(['serial_numbers' => ['DUP-1']]);
        $orders = Order::count();
        $phone = '01711000002';

        $this->post(route('admin.order.store'), $this->posPayload($phone))->assertRedirect();

        $this->assertSame($orders, Order::count(), 'No order is left behind for the refused sale.');
        $this->assertFalse(Customer::where('phone', $phone)->exists());
        $this->assertSame(20, $this->remaining(), 'A pending order holds no stock, so the refused sale changes none.');
    }

    public function test_pos_update_that_fails_keeps_the_order_lines_and_stock_as_they_were(): void
    {
        $order = $this->pendingOrder(1);
        $detail = OrderDetails::where('order_id', $order->id)->firstOrFail();
        Cart::instance('pos_shopping')->add([
            'id' => $this->product->id, 'name' => 'Legacy item', 'qty' => 2, 'price' => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100, 'details_id' => $detail->id],
        ]);
        $this->failAfterStockMoves();
        $phone = '01711000003';

        $this->post(route('admin.order.update'), $this->posUpdatePayload($order, $phone, 'paid'))->assertRedirect();

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame(1, (int) $detail->fresh()->qty);
        $this->assertSame(20, $this->remaining());
        $this->assertFalse(Customer::where('phone', $phone)->exists());
    }

    public function test_pos_update_applies_the_status_and_its_stock_together(): void
    {
        $order = $this->pendingOrder(1);
        $detail = OrderDetails::where('order_id', $order->id)->firstOrFail();
        Cart::instance('pos_shopping')->add([
            'id' => $this->product->id, 'name' => 'Legacy item', 'qty' => 2, 'price' => 200,
            'options' => ['product_id' => $this->product->id, 'purchase_price' => 100, 'details_id' => $detail->id],
        ]);

        $this->post(route('admin.order.update'), $this->posUpdatePayload($order, '01711000004', 'paid'))->assertRedirect();

        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertSame(2, (int) $detail->fresh()->qty);
        $this->assertSame(18, $this->remaining());
    }

    public function test_return_that_fails_keeps_the_return_status_and_does_not_restock(): void
    {
        $order = $this->pendingOrder(2);
        $this->confirm($order);
        Order::whereKey($order->id)->update(['order_status' => 'return_approved']);
        $this->assertSame(18, $this->remaining());
        $this->failAfterStockMoves();

        $this->postJson(route('admin.order.markReturned'), ['order_id' => $order->id])
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);

        $this->assertSame('return_approved', $order->fresh()->order_status);
        $this->assertSame(18, $this->remaining());
    }

    public function test_return_applies_the_status_and_restocks_together(): void
    {
        $order = $this->pendingOrder(2);
        $this->confirm($order);
        Order::whereKey($order->id)->update(['order_status' => 'return_approved']);

        $this->postJson(route('admin.order.markReturned'), ['order_id' => $order->id])->assertOk();

        $this->assertSame('returned', $order->fresh()->order_status);
        $this->assertSame(20, $this->remaining());
    }

    public function test_cancel_that_fails_keeps_the_status_and_the_stock(): void
    {
        $order = $this->pendingOrder(2);
        $this->confirm($order);
        $this->failAfterStockMoves();

        $this->postJson(route('admin.order.cancel'), ['order_id' => $order->id])
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);

        $this->assertSame('confirmed', $order->fresh()->order_status);
        $this->assertSame(18, $this->remaining());
    }

    public function test_a_second_cancel_is_refused_and_does_not_restock_again(): void
    {
        $order = $this->pendingOrder(2);
        $this->confirm($order);

        $this->postJson(route('admin.order.cancel'), ['order_id' => $order->id])->assertOk();
        $this->assertSame(20, $this->remaining());

        $this->postJson(route('admin.order.cancel'), ['order_id' => $order->id])
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(20, $this->remaining(), 'The second cancel reads the cancelled row and restocks nothing.');
    }

    /** Moves a pending order to confirmed the way the admin does: the status is saved and the stock is taken out. */
    private function confirm(Order $order): void
    {
        Order::whereKey($order->id)->update(['order_status' => 'confirmed']);
        app(OrderStatusService::class)->handleStatusChange($order->fresh(), 'pending', 'confirmed');
    }

    /** The order-change form's fields, as the admin page posts them. */
    private function changePayload(Order $order, string $status): array
    {
        return [
            'id' => $order->id, 'status' => $status, 'name' => 'Legacy buyer', 'phone' => '01700000010',
            'address' => 'Dhaka', 'area' => $this->shipping->id,
        ];
    }

    /** Puts one cart line of the product into the POS cart, with the given serial numbers. */
    private function seedPosCart(array $options): void
    {
        Cart::instance('pos_shopping')->add([
            'id' => $this->product->id, 'name' => 'Legacy item', 'qty' => 1, 'price' => 200,
            'options' => array_merge(['product_id' => $this->product->id, 'purchase_price' => 100], $options),
        ]);
    }

    private function posPayload(string $phone): array
    {
        return [
            'name' => 'POS walk-in', 'phone' => $phone, 'address' => 'Mirpur, Dhaka', 'area' => $this->shipping->id,
            'payment_type' => 'paid', 'payment_method' => 'Cash', 'paid_amount' => 260,
        ];
    }

    private function posUpdatePayload(Order $order, string $phone, string $paymentStatus): array
    {
        return [
            'order_id' => $order->id, 'name' => 'POS walk-in', 'phone' => $phone, 'address' => 'Mirpur, Dhaka',
            'area' => $this->shipping->id, 'payment_gateway' => 'cod', 'payment_status' => $paymentStatus,
        ];
    }
}
