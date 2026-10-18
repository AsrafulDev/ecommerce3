<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\ShippingCharge;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Warranty pipeline, end to end: a warranty-tier item is sold through POS, the
 * warranty sale is recorded against the order and batch, and a claim is filed
 * against it. Filing a claim must not move money or stock by itself.
 */
class WarrantyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    public function test_warranty_sale_then_claim_does_not_move_money_or_stock(): void
    {
        // ── Stock + capital ─────────────────────────────────────────────────
        $category = \App\Models\Category::create([
            'name' => 'Electronics', 'slug' => 'electronics-' . uniqid(), 'status' => 1, 'parent_id' => 0,
        ]);
        $product = Product::create([
            'name'           => 'Warranty Speaker',
            'slug'           => 'warranty-speaker-' . uniqid(),
            'category_id'    => $category->id,
            'product_code'   => 'WS-' . uniqid(),
            'purchase_price' => 100,
            'new_price'      => 200,
            'stock'          => 0,
            'status'         => 1,
            'warranty_method' => 'active',
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 5, 'unit_cost' => 100]);
        \App\Models\FundTransaction::create([
            'direction' => 'in', 'source' => 'manual_add', 'amount' => 10000, 'created_by' => 1,
        ]);

        // ── Warranty tier: 1 year store warranty costing 50 extra ──────────
        $tier = ProductWarrantyTier::create([
            'product_id'     => $product->id,
            'tier_name'      => 'Store 1 Year',
            'warranty_type'  => 'store_warranty',
            'warranty_days'  => 365,
            'price'          => 50,
            'is_active'      => 1,
            'sort_order'     => 1,
        ]);

        // ── POS sale: 1 unit at 200 + 50 warranty, + 60 delivery ───────────
        $shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
        Cart::instance('pos_shopping')->add([
            'id'      => $product->id,
            'name'    => $product->name,
            'qty'     => 1,
            // Cart price = base price + warranty adjustment, as the POS adds it.
            'price'   => 250,
            'options' => [
                'product_id'          => $product->id,
                'purchase_price'      => 100,
                'base_price'          => 200,
                'warranty_tier_id'    => $tier->id,
                'warranty_adjustment' => 50,
            ],
        ]);

        $this->post('/admin/order/store', [
            'name'           => 'Warranty Customer',
            'phone'          => '01722222222',
            'address'        => 'Dhanmondi, Dhaka',
            'area'           => $shipping->id,
            'payment_type'   => 'paid',
            'payment_method' => 'Cash',
            'paid_amount'    => 310,
            'save_as_pending' => 0,
        ])->assertRedirect(route('admin.order.create'));

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame('completed', $order->order_status, 'warranty sale must complete');

        // ── Warranty sale recorded against the order and its batch ─────────
        $sale = WarrantySale::where('order_id', $order->id)->firstOrFail();
        $this->assertSame($product->id, (int) $sale->product_id);
        $this->assertSame(365, (int) $sale->warranty_days);
        $this->assertNotNull($sale->stock_batch_id ?? $sale->batch_id ?? null, 'warranty sale must point at a batch');

        $order->refresh();
        $this->assertEqualsWithDelta(310.0, (float) $order->amount, 0.01, 'order total = 200 item + 50 warranty + 60 delivery');
        $this->assertEqualsWithDelta(310.0, (float) $order->paid_amount, 0.01, 'customer paid the full order');

        // Stock and money after the sale
        $this->assertSame(4, (int) $product->fresh()->stock, 'sale of 1 unit leaves 4');
        $this->assertSame(4, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));
        $this->assertEqualsWithDelta(10000 + 310, FundHelper::balance(), 0.01, 'sale cash reaches the till');

        // ── File a claim: must not move money or stock ─────────────────────
        $tillBefore  = FundHelper::balance();
        $stockBefore = (int) $product->fresh()->stock;

        $this->post(route('admin.warranty.claims.file-for-customer'), [
            'warranty_sale_id'  => $sale->id,
            'issue_description' => 'Speaker crackles at high volume',
            'issue_type'        => 'defect',
        ])->assertSessionHasNoErrors();

        $claim = WarrantyClaim::where('warranty_sale_id', $sale->id)->firstOrFail();
        $this->assertSame('submitted', $claim->status instanceof \BackedEnum ? $claim->status->value : (string) $claim->status);
        $this->assertEqualsWithDelta($tillBefore, FundHelper::balance(), 0.01, 'filing a claim must not move cash');
        $this->assertSame($stockBefore, (int) $product->fresh()->stock, 'filing a claim must not move stock');

        // A second claim on the same warranty is refused while one is active.
        $this->post(route('admin.warranty.claims.file-for-customer'), [
            'warranty_sale_id'  => $sale->id,
            'issue_description' => 'Second attempt on same warranty',
            'issue_type'        => 'defect',
        ]);
        $this->assertSame(1, WarrantyClaim::where('warranty_sale_id', $sale->id)->count(), 'only one active claim per warranty');

        // ── Instant replacement: 1 unit out of sellable stock, damaged unit into damage stock ──
        $tillBeforeReplacement = FundHelper::balance();
        $this->post(route('admin.warranty.claims.replacement', $claim->id), [
            'damage_type'            => 'partial',
            'replacement_product_id' => $product->id,
            'replacement_sn'         => 'NEW-SN-' . uniqid(),
            'condition_note'         => 'Cone cracked',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, (int) $product->fresh()->stock, 'replacement takes 1 unit from sellable stock');
        $this->assertSame(3, (int) StockBatch::where('product_id', $product->id)->sum('remaining_qty'));
        $this->assertSame(
            1,
            \App\Models\DamageProduct::where('warranty_claim_id', $claim->id)->count(),
            'the damaged unit must be recorded in damage stock'
        );
        $this->assertEqualsWithDelta($tillBeforeReplacement, FundHelper::balance(), 0.01, 'a free replacement moves no cash');
    }
}
