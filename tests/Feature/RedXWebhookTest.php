<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Courierapi;
use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\SmsGateway;
use App\Models\StockBatch;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 2.3 (UPDATE-PLAN) — RedX webhook must write ENUM statuses via
 * Order::transitionTo(), reuse the ONE shared stock engine
 * (OrderController::handleStockChange), and guard the fund credit (idempotent).
 * No more raw int writes / private direct-stock-mutation copy.
 */
class RedXWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);
        SmsGateway::query()->delete(); // no real SMS calls
        Courierapi::create(['type' => 'redx', 'status' => 1, 'url' => 'https://openapi.redx.test', 'token' => 'test-token']);
    }

    /** RedX's parcel API reports this status for the tracking id. */
    protected function redxSays(string $redxStatus): void
    {
        Http::fake(['https://openapi.redx.test/parcel/info/*' => Http::response(['parcel' => ['status' => $redxStatus]], 200)]);
    }

    protected function makeProduct(): Product
    {
        $product = Product::create([
            'name'           => 'RedX Product',
            'slug'           => 'redx-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'RDX-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 10, 'unit_cost' => 100]);
        return $product;
    }

    protected function makeActiveOrder(Product $product, string $tracking, int $qty): Order
    {
        $customer = Customer::create([
            'name'     => 'RedX Customer',
            'slug'     => 'redx-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        $order = Order::create([
            'invoice_id'          => 'INV-RDX-'.uniqid(),
            'amount'              => 200 * $qty,
            'discount'            => 0,
            'shipping_charge'     => 0,
            'customer_id'         => $customer->id,
            'order_status'        => OrderStatus::PACKED->value,
            'courier_type'        => 'redx',
            'courier_tracking_id' => $tracking,
        ]);
        OrderDetails::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 200,
            'qty'            => $qty,
        ]);

        // Stock was already consumed while the order was active (packed).
        app(StockManagementService::class)->stockOut($product, $qty, ['type' => 'sale', 'id' => $order->id]);

        return $order;
    }

    public function test_returned_webhook_writes_enum_and_restocks_via_shared_engine(): void
    {
        $product = $this->makeProduct();
        $order = $this->makeActiveOrder($product, 'RDX-RET-1', 2);

        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->redxSays('returned');

        $response = $this->postJson(route('redx.webhook'), [
            'tracking_number' => 'RDX-RET-1',
            'invoice_number'  => $order->invoice_id,
            'status'          => 'returned',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);

        // Enum status (not an int), restocked through sale_return.
        $this->assertSame(OrderStatus::CANCELLED->value, $order->fresh()->order_status);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count());

        // A system order note was written by transitionTo.
        $this->assertTrue($order->notes()->exists());
    }

    public function test_delivered_webhook_credits_fund_only_once(): void
    {
        $product = $this->makeProduct();
        $order = $this->makeActiveOrder($product, 'RDX-DEL-1', 1);

        $this->redxSays('delivered');
        $payload = [
            'tracking_number' => 'RDX-DEL-1',
            'invoice_number'  => $order->invoice_id,
            'status'          => 'delivered',
        ];

        // Deliver twice — must reach COMPLETED but credit the fund only ONCE.
        $this->postJson(route('redx.webhook'), $payload)->assertStatus(200);
        $this->postJson(route('redx.webhook'), $payload)->assertStatus(200);

        $this->assertSame(OrderStatus::COMPLETED->value, $order->fresh()->order_status);
        $this->assertSame(1, FundTransaction::where('source', 'sale')->where('source_id', $order->id)->count());
        // Stock stays consumed (completed is still active) — no drift, no double-out.
        $this->assertSame(9, (int) $product->fresh()->stock);
    }

    public function test_a_forged_delivered_callback_cannot_change_the_order_when_redx_says_otherwise(): void
    {
        $product = $this->makeProduct();
        $order = $this->makeActiveOrder($product, 'RDX-FORGE-1', 1);
        $this->redxSays('delivery-in-progress');

        $this->postJson(route('redx.webhook'), ['tracking_number' => 'RDX-FORGE-1', 'status' => 'delivered'])->assertStatus(200);

        $this->assertSame(OrderStatus::PACKED->value, $order->fresh()->order_status);
        $this->assertSame(0, FundTransaction::where('source', 'sale')->where('source_id', $order->id)->count(), 'no cash credited');
        $this->assertSame(9, (int) $product->fresh()->stock, 'no stock moved');
    }

    public function test_a_callback_is_refused_with_503_and_changes_nothing_when_redx_cannot_be_asked(): void
    {
        $product = $this->makeProduct();
        $order = $this->makeActiveOrder($product, 'RDX-DOWN-1', 1);
        Http::fake(['https://openapi.redx.test/parcel/info/*' => Http::response('down', 500)]);

        $this->postJson(route('redx.webhook'), ['tracking_number' => 'RDX-DOWN-1', 'status' => 'returned'])
            ->assertStatus(503);

        $this->assertSame(OrderStatus::PACKED->value, $order->fresh()->order_status);
        $this->assertSame(9, (int) $product->fresh()->stock);
    }

    public function test_a_callback_for_an_unknown_tracking_number_does_not_even_ask_redx(): void
    {
        Http::fake();

        $this->postJson(route('redx.webhook'), ['tracking_number' => 'RDX-NONE-1', 'status' => 'delivered'])
            ->assertStatus(404);

        Http::assertNothingSent();
    }

    public function test_a_callback_whose_invoice_does_not_match_the_tracking_order_is_refused(): void
    {
        $product = $this->makeProduct();
        $order = $this->makeActiveOrder($product, 'RDX-INV-1', 1);
        $this->redxSays('returned');

        $this->postJson(route('redx.webhook'), [
            'tracking_number' => 'RDX-INV-1', 'invoice_number' => 'INV-SOMEONE-ELSE', 'status' => 'returned',
        ])->assertStatus(404);

        $this->assertSame(OrderStatus::PACKED->value, $order->fresh()->order_status);
    }

    public function test_a_tracking_number_of_another_courier_is_not_matched(): void
    {
        $product = $this->makeProduct();
        $order = $this->makeActiveOrder($product, 'RDX-PATHAO-1', 1);
        $order->update(['courier_type' => 'pathao']);
        Http::fake();

        $this->postJson(route('redx.webhook'), ['tracking_number' => 'RDX-PATHAO-1', 'status' => 'returned'])
            ->assertStatus(404);

        Http::assertNothingSent();
        $this->assertSame(OrderStatus::PACKED->value, $order->fresh()->order_status);
    }
}
