<?php

namespace Tests\Feature\Courier;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use App\Models\FundTransaction;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use App\Services\TransactionReset\SaleResetService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Courierapi;
use Tests\TestCase;

/**
 * Courier webhooks and the sales reset both change the same order. Both lock the order row and re-read it, so a
 * duplicate delivery is a no-op, a webhook after a reset is refused, and a reset reads the courier-synced state.
 */
class CourierWebhookConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $admin;

    private int $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $this->admin = User::first();
        $this->admin->update(['password' => bcrypt('password')]);
        $this->actingAs($this->admin, 'admin');
        \App\Models\GeneralSetting::query()->update(['vat_enabled' => false]);
        $this->buyer = Customer::create([
            'name' => 'Courier buyer', 'slug' => 'cb-'.uniqid(), 'phone' => '01700000004',
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;

        $category = Category::create(['name' => 'Cour', 'slug' => 'cour-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Courier item', 'slug' => 'cour-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'CO-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
        Courierapi::create(['type' => 'redx', 'status' => 1, 'url' => 'https://openapi.redx.test', 'token' => 'test-token']);
    }

    /** RedX's parcel API reports this status for the tracking id. */
    private function redxSays(string $redxStatus): void
    {
        Http::fake(['https://openapi.redx.test/parcel/info/*' => Http::response(['parcel' => ['status' => $redxStatus]], 200)]);
    }

    /** A packed order sent with a courier, holding stock through the normal allocation. */
    private function packedWithCourier(string $tracking): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-CR-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->buyer, 'order_status' => 'pending', 'created_at' => now(),
            'courier_type' => 'redx', 'courier_tracking_id' => $tracking, 'courier_sent_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Courier item', 'purchase_price' => 100,
        ]);
        app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::PACKED, 'Packed for the test.');

        return $order->fresh();
    }

    private function webhook(string $tracking, string $status, ?string $invoice = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/redx/webhook', array_filter([
            'tracking_number' => $tracking, 'status' => $status, 'invoice_number' => $invoice,
        ]));
    }

    public function test_a_duplicate_returned_delivery_restocks_once(): void
    {
        $order = $this->packedWithCourier('RX-DUP-1');
        $this->assertSame(8, (int) $this->product->fresh()->stock);
        $this->redxSays('returned');

        $this->webhook('RX-DUP-1', 'returned')->assertOk();
        $this->webhook('RX-DUP-1', 'returned')->assertOk();

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, (int) $this->product->fresh()->stock, 'restocked once, not twice');
    }

    public function test_a_webhook_after_a_reset_is_refused_and_overwrites_nothing(): void
    {
        $order = $this->packedWithoutCourier();
        app(SaleResetService::class)->reverse($order, $this->admin, 'Reset before the courier message.');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, (int) $this->product->fresh()->stock, 'the reset restocked the sale once');

        // A courier message for the reset order arrives late.
        $order->update(['courier_tracking_id' => 'RX-AFTER-1', 'courier_type' => 'redx']);
        $this->redxSays('delivered');
        $this->webhook('RX-AFTER-1', 'delivered')->assertOk();

        $this->assertSame('cancelled', $order->fresh()->order_status, 'cancelled cannot become completed');
        $this->assertSame(10, (int) $this->product->fresh()->stock, 'no second restock or sale');
        $this->assertSame(0, \App\Models\FundTransaction::where('source', 'sale')->where('source_id', $order->id)->count(), 'no cash credited');
    }

    public function test_a_reset_reads_the_courier_synced_state_and_is_refused_while_the_consignment_exists(): void
    {
        $order = $this->packedWithCourier('RX-SYNC-1');
        $this->redxSays('ready-for-delivery');
        $this->webhook('RX-SYNC-1', 'ready-for-delivery')->assertOk();

        try {
            app(SaleResetService::class)->reverse($order->fresh(), $this->admin, 'Reset after the courier sync.');
            $this->fail('a consignment must block the reset');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('courier consignment', $e->getMessage());
        }

        $this->assertSame('packed', $order->fresh()->order_status, 'nothing was reset from a stale status');
    }

    public function test_an_unmatched_webhook_without_an_invoice_number_changes_no_order(): void
    {
        $other = $this->packedWithCourier('RX-OTHER-1');

        $this->webhook('RX-NOPE-9', 'returned')->assertNotFound();

        $this->assertSame('packed', $other->fresh()->order_status);
        $this->assertSame(8, (int) $this->product->fresh()->stock);
    }

    public function test_the_webhook_and_the_reset_both_lock_the_order_row(): void
    {
        $order = $this->packedWithCourier('RX-LOCK-1');
        $this->redxSays('returned');

        DB::enableQueryLog();
        $this->webhook('RX-LOCK-1', 'returned')->assertOk();
        $log = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();

        $this->assertMatchesRegularExpression('/from `orders`.*for update/is', $log, 'the webhook locks the order row');

        $plain = $this->packedWithoutCourier();
        DB::enableQueryLog();
        app(SaleResetService::class)->reverse($plain, $this->admin, 'Lock check.');
        $resetLog = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();

        $this->assertMatchesRegularExpression('/from `orders`.*for update/is', $resetLog, 'the reset locks the same row');
        $this->assertNotNull($order);
    }

    /** A packed order with no courier link, for the reset-side checks. */
    private function packedWithoutCourier(): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-PL-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->buyer, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Courier item', 'purchase_price' => 100,
        ]);
        app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::PACKED, 'Packed for the test.');

        return $order->fresh();
    }
}
