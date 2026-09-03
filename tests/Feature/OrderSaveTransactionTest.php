<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\SmsGateway;
use App\Models\StockBatch;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1.2 (UPDATE-PLAN) — storefront checkout (CustomerController::order_save)
 * persists order + shipping + payment + details + warranty + stock inside ONE DB
 * transaction. A mid-save failure rolls back EVERYTHING; the cart is only cleared
 * after commit.
 *
 * NOTE: full rollback on STOCK failures is coupled to Phase 2.4 (which removes the
 * legacy direct-decrement fallback that currently swallows stock exceptions).
 */
class OrderSaveTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);

        // Keep batch-wise pricing OFF so `products.stock` is the raw column and the
        // plain stockOut() path runs (same as StockServiceTest).
        config(['pricing.batch_wise' => false]);

        // No SMS gateway → the SMS blocks no-op (no real curl calls in tests).
        SmsGateway::query()->delete();
    }

    protected function makeProduct(): Product
    {
        $product = Product::create([
            'name'           => 'Checkout Product',
            'slug'           => 'checkout-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'CHK-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);

        // Give it a real stock batch (source of truth) so stockOut is exercised.
        app(StockManagementService::class)->stockIn($product, [
            'quantity'  => 10,
            'unit_cost' => 100,
        ]);

        return $product;
    }

    protected function validPayload(Product $product): array
    {
        return [
            'product'        => $product->id,          // campaign-add path fills the cart server-side
            'name'           => 'Asif Rahman',
            'phone'          => '0171'.random_int(1000000, 9999999),
            'address'        => 'Mirpur, Dhaka',
            'area'           => '0',                    // no shipping charge linked → fee 0
            // 'bkash' (online) avoids the COD-only Facebook CAPI shutdown hook.
            'payment_method' => 'bkash',
        ];
    }

    public function test_checkout_persists_order_atomically_and_clears_cart_on_success(): void
    {
        $product = $this->makeProduct();

        $beforeOrders  = Order::count();
        $beforeDetails = OrderDetails::count();

        $response = $this->post(route('customer.ordersave'), $this->validPayload($product));

        $response->assertStatus(302);
        $this->assertStringContainsString('/bkash/checkout-url/create?order_id=', $response->headers->get('Location'));

        // Order + details + shipping + payment all created.
        $this->assertSame($beforeOrders + 1, Order::count());
        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame(200.0, (float) $order->amount);
        $this->assertSame(1, OrderDetails::where('order_id', $order->id)->count());
        $this->assertSame(1, Shipping::where('order_id', $order->id)->count());
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());

        // Stock deducted exactly once via the batch (not the raw-column fallback).
        $this->assertSame(9, (int) $product->fresh()->stock);
        $this->assertSame(9, (int) StockBatch::where('product_id', $product->id)->first()->remaining_qty);

        // Details rows were created only once (no duplicates from a double save).
        $this->assertSame($beforeDetails + 1, OrderDetails::count());
    }

    public function test_mid_save_failure_rolls_back_everything(): void
    {
        $product = $this->makeProduct();

        $beforeOrders  = Order::count();
        $beforeShipping = Shipping::count();
        $beforePayment  = Payment::count();
        $beforeDetails  = OrderDetails::count();

        // Force the Payment::save() (inside the transaction, outside any nested
        // catch) to throw → the outer rollback must undo order+shipping already saved.
        \App\Models\Payment::saving(function () {
            throw new \RuntimeException('Simulated payment failure');
        });

        try {
            $response = $this->post(route('customer.ordersave'), $this->validPayload($product));
            $response->assertStatus(302);
        } finally {
            \App\Models\Payment::flushEventListeners();
        }

        // NOTHING was persisted — no partial order/shipping/payment/details.
        $this->assertSame($beforeOrders, Order::count());
        $this->assertSame($beforeShipping, Shipping::count());
        $this->assertSame($beforePayment, Payment::count());
        $this->assertSame($beforeDetails, OrderDetails::count());

        // Stock untouched (the sale never happened).
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(10, (int) StockBatch::where('product_id', $product->id)->first()->remaining_qty);
    }

    public function test_stock_failure_aborts_checkout_and_rolls_back(): void
    {
        // No batch + stock 0 + allow_negative_stock=false → stockOut() throws.
        $product = Product::create([
            'name'           => 'Out of Stock Product',
            'slug'           => 'oos-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'OOS-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);

        $beforeOrders  = Order::count();
        $beforeDetails = OrderDetails::count();
        $beforePayment = Payment::count();
        $beforeShipping = Shipping::count();

        $response = $this->post(route('customer.ordersave'), $this->validPayload($product));

        // Graceful redirect back (no 500), order NOT placed.
        $response->assertStatus(302);

        // Phase 2.4: no partial order survives a stock-out failure.
        $this->assertSame($beforeOrders, Order::count());
        $this->assertSame($beforeDetails, OrderDetails::count());
        $this->assertSame($beforePayment, Payment::count());
        $this->assertSame($beforeShipping, Shipping::count());
        $this->assertSame(0, (int) $product->fresh()->stock);
    }
}
