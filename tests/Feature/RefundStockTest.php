<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Refund;
use App\Models\StockBatch;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2.1 (UPDATE-PLAN) — refunds are money-only. Stock restock belongs to the
 * order CANCEL path (OrderController::handleStockChange → stockIn sale_return),
 * so RefundController::process() must NEVER touch product stock. Otherwise a
 * cancelled order gets double-restored AND products.stock drifts (raw += with no
 * stock_batches row).
 */
class RefundStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    protected function makeProduct(): Product
    {
        return Product::create([
            'name'           => 'Refund Stock Product',
            'slug'           => 'refund-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'REF-'.uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
        ]);
    }

    protected function makeCustomer(): Customer
    {
        return Customer::create([
            'name'     => 'Refund Customer',
            'slug'     => 'refund-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);
    }

    public function test_refund_process_does_not_restock_a_cancelled_order(): void
    {
        $product = $this->makeProduct();
        $customer = $this->makeCustomer();
        $svc = app(StockManagementService::class);

        // Purchase 10 → stock 10.
        $svc->stockIn($product, ['quantity' => 10, 'unit_cost' => 100]);

        // Order consumed 2 while active → batch 8 / stock 8.
        $svc->stockOut($product, 2, ['type' => 'sale', 'id' => 1]);

        // Cancel restocks via handleStockChange → stockIn sale_return → stock 10.
        $svc->stockIn($product, ['quantity' => 2, 'unit_cost' => 100, 'reference_type' => 'sale_return', 'reference_id' => 1]);

        // The order itself is CANCELLED with 2 units of detail.
        $order = Order::create([
            'invoice_id'      => 'INV-REFUND-'.uniqid(),
            'amount'          => 400,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => OrderStatus::CANCELLED->value,
        ]);
        OrderDetails::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 200,
            'qty'            => 2,
        ]);

        $refund = Refund::create([
            'order_id'    => $order->id,
            'customer_id' => $customer->id,
            'refund_id'   => Refund::generateRefundId(),
            'amount'      => 400,
            'status'      => 'approved',
        ]);

        $stockBefore    = (int) $product->fresh()->stock;
        $remainingBefore = (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty');

        $this->post(route('admin.refunds.process', $refund->id), [
            'transaction_id'      => 'TXN-REF-1',
            'refund_method'       => 'bkash',
            'refund_account'      => '01711111111',
            'refund_account_name' => 'Refund Customer',
        ])->assertStatus(302);

        // Refund itself processed…
        $this->assertSame('processed', $refund->fresh()->status);

        // …but stock is NOT double-restored and batches are NOT drifted.
        $this->assertSame($stockBefore, (int) $product->fresh()->stock);
        $this->assertSame($remainingBefore, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));
    }
}
