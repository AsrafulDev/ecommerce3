<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\User;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POS "Save & Pending" button.
 *
 * Saving as pending must PARK the sale: status pending, nothing collected
 * (paid = 0, full due) and no stock movement. The plain "Complete Sale" path
 * must keep behaving exactly as before.
 */
class PosPendingOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private ShippingCharge $shipping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    private function seedPosCart(): void
    {
        $this->product = Product::create([
            'name'           => 'POS Pending Product',
            'slug'           => 'pos-pending-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'POS-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);

        app(StockManagementService::class)->stockIn($this->product, [
            'quantity'  => 10,
            'unit_cost' => 100,
        ]);
        $this->product->refresh();

        $this->shipping = ShippingCharge::create([
            'name'   => 'Inside Dhaka',
            'amount' => 60,
            'status' => '1',
        ]);

        Cart::instance('pos_shopping')->add([
            'id'      => $this->product->id,
            'name'    => $this->product->name,
            'qty'     => 2,
            'price'   => 200,
            'options' => [
                'product_id'     => $this->product->id,
                'purchase_price' => 100,
            ],
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name'           => 'POS Walk-in',
            'phone'          => '017'.random_int(10000000, 99999999),
            'address'        => 'Mirpur, Dhaka',
            'area'           => $this->shipping->id,
            'payment_type'   => 'paid',
            'payment_method' => 'Cash',
            'paid_amount'    => 460,
        ], $extra);
    }

    public function test_save_as_pending_parks_order_without_collecting_or_deducting(): void
    {
        $this->seedPosCart();
        $stockBefore = (int) $this->product->fresh()->stock;

        // Note paid_amount = 460 is deliberately sent: the pending button must ignore it.
        $this->post('/admin/order/store', $this->payload(['save_as_pending' => 1]))
            ->assertRedirect(route('admin.order.create'));

        $this->assertSame(1, Order::count(), 'exactly one order should be created');
        $order = Order::first();

        $this->assertSame('pending', $order->order_status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(0.0, (float) $order->paid_amount, 'pending must collect nothing');
        $this->assertSame((float) $order->amount, (float) $order->due_amount, 'pending leaves the full amount due');
        $this->assertSame(
            $stockBefore,
            (int) $this->product->fresh()->stock,
            'no stock should move for a pending order'
        );
    }

    public function test_complete_sale_still_completes_and_collects(): void
    {
        $this->seedPosCart();

        $this->post('/admin/order/store', $this->payload(['save_as_pending' => 0]))
            ->assertRedirect(route('admin.order.create'));

        $this->assertSame(1, Order::count());
        $order = Order::first();

        $this->assertSame('completed', $order->order_status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(460.0, (float) $order->paid_amount);
        $this->assertSame(0.0, (float) $order->due_amount);
    }
}
