<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end site lifecycle, driven through the real admin HTTP routes:
 *
 *   install → capital → product create → purchase (stocking) → POS sale
 *   → balancing
 *
 * Each phase asserts the business result AND the ledgers it touches, so a
 * failure names the broken step. Phases share state, so they run in order
 * inside one test.
 */
class SiteLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    public function test_install_to_balancing_full_lifecycle(): void
    {
        // ── 1. Install: seeded defaults exist ──────────────────────────────
        // A fresh install seeds no categories, so the owner creates one before products.
        $this->assertSame(0, \App\Models\Category::count(), 'fresh install has no categories');
        $this->assertGreaterThan(0, User::count(), 'install must create an admin user');
        $this->assertGreaterThan(0, \Spatie\Permission\Models\Permission::count(), 'install must seed permissions');
        $this->assertSame(0.0, FundHelper::balance(), 'a fresh install has an empty till');

        // ── 2. Owner capital into the till ─────────────────────────────────
        $this->post(route('admin.transactions.store'), [
            'date'     => now()->toDateString(),
            'amount'   => 10000,
            'category' => 'owner_capital',
            'note'     => 'Opening capital',
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(10000.0, FundHelper::balance(), 0.01, 'capital must land in the till');

        // ── 3. Product create ──────────────────────────────────────────────
        $this->post(route('categories.store'), ['name' => 'Phones', 'status' => 1])
            ->assertRedirect(route('categories.index'));
        $category = \App\Models\Category::where('name', 'Phones')->firstOrFail();

        $response = $this->post(route('products.store'), [
            'name'           => 'Lifecycle Phone',
            'category_id'    => $category->id,
            'product_type'   => 'simple',
            'description'    => 'End-to-end test product',
            'new_price'      => 200,
            'purchase_price' => 100,
            'stock'          => 0,
            'status'         => 1,
            'warranty_method' => 'active',
        ]);
        $response->assertSessionHasNoErrors();
        $product = Product::where('name', 'Lifecycle Phone')->firstOrFail();
        $this->assertSame(0, (int) $product->stock, 'product create must not set stock directly');

        // ── 4. Stocking: purchase 10 units @ 100 ───────────────────────────
        $supplier = Supplier::create(['name' => 'Lifecycle Supplier']);
        $this->post(route('purchases.store'), [
            'supplier_id'   => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'invoice_no'    => 'INV-LC-' . uniqid(),
            'items'         => [
                ['product_id' => $product->id, 'qty' => 10, 'unit_cost' => 100],
            ],
            'paid_amount'   => 600,
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(10, (int) $product->stock, 'purchase must stock 10 units');
        $this->assertSame(10, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));
        $this->assertEqualsWithDelta(10000 - 600, FundHelper::balance(), 0.01, 'purchase paid 600 out of the till');

        // ── 5. POS sale: 2 units @ 200, delivered and paid in full ─────────
        $shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
        Cart::instance('pos_shopping')->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => 2,
            'price'   => 200,
            'options' => ['product_id' => $product->id, 'purchase_price' => 100],
        ]);

        $this->post('/admin/order/store', [
            'name'           => 'Lifecycle Customer',
            'phone'          => '01711111111',
            'address'        => 'Mirpur, Dhaka',
            'area'           => $shipping->id,
            'payment_type'   => 'paid',
            'payment_method' => 'Cash',
            'paid_amount'    => 460,
            'save_as_pending' => 0,
        ])->assertRedirect(route('admin.order.create'));

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame('completed', $order->order_status, 'POS sale must complete');
        $this->assertSame('paid', $order->payment_status);
        $this->assertEqualsWithDelta(460.0, (float) $order->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $order->due_amount, 0.01);

        $product->refresh();
        $this->assertSame(8, (int) $product->stock, 'sale must deduct 2 units');
        $this->assertSame(8, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));

        // Money: till = capital − purchase paid + sale collected
        $this->assertEqualsWithDelta(10000 - 600 + 460, FundHelper::balance(), 0.01, 'sale cash must reach the till');
        $this->assertFundMatchesLedger();

        // ── 6. Return: request → approve → mark returned, stock comes back ─
        $this->post(route('admin.order.requestReturn'), ['order_id' => $order->id])->assertOk();
        $this->post(route('admin.order.approveReturn'), ['order_id' => $order->id])->assertOk();
        $this->post(route('admin.order.markReturned'), ['order_id' => $order->id])->assertOk();

        $order->refresh();
        $this->assertSame('returned', $order->order_status instanceof \BackedEnum ? $order->order_status->value : (string) $order->order_status, 'order must be returned');
        $product->refresh();
        $this->assertSame(10, (int) $product->stock, 'a returned sale must restock its 2 units');
        $this->assertSame(10, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));

        // ── 7. Refund: approve pays out once; a second approve is refused ──
        $refund = \App\Models\Refund::create([
            'order_id'    => $order->id,
            'customer_id' => $order->customer_id,
            'refund_id'   => 'RF-LC-' . uniqid(),
            'amount'      => 460,
            'shipping_charge' => 0,
            'include_shipping' => false,
            'reason'      => 'Returned item',
            'status'      => 'pending',
        ]);

        $this->post(route('admin.refunds.approve', $refund->id))->assertRedirect();
        $this->assertEqualsWithDelta(10000 - 600 + 460 - 460, FundHelper::balance(), 0.01, 'approved refund pays out 460');

        $this->post(route('admin.refunds.approve', $refund->id))->assertRedirect();
        $this->assertSame(
            1,
            FundTransaction::where('source', 'refund')->where('source_id', $refund->id)->count(),
            'a refund must be paid out exactly once'
        );
        $this->assertFundMatchesLedger();

        // ── 8. Expense: rent 50 out of the till ────────────────────────────
        $this->post(route('admin.transactions.store'), [
            'date'     => now()->toDateString(),
            'amount'   => 50,
            'category' => 'rent',
            'note'     => 'Shop rent',
        ])->assertSessionHasNoErrors();

        // ── 9. Balancing: final till, stock and ledgers agree ──────────────
        $this->assertEqualsWithDelta(
            10000 - 600 + 460 - 460 - 50,
            FundHelper::balance(),
            0.01,
            'final till = capital − purchase + sale − refund − expense'
        );
        $this->assertFundMatchesLedger();
        $this->get(route('admin.accounts.reports.index'))->assertOk();
    }

    private function assertFundMatchesLedger(): void
    {
        $in = (float) FundTransaction::where('direction', 'in')->sum('amount');
        $out = (float) FundTransaction::where('direction', 'out')->sum('amount');
        $this->assertEqualsWithDelta($in - $out, FundHelper::balance(), 0.01, 'till must equal Σin − Σout');
    }
}
