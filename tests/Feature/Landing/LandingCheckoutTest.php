<?php

namespace Tests\Feature\Landing;

use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\ShippingCharge;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing order form: the chosen variant sets the price and the cart option, and
 * an unavailable or missing option is refused.
 */
class LandingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ShippingCharge $area;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);

        // An installed site has a settings row; without one the order is sent to the installer.
        \App\Models\GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);

        $category = \App\Models\Category::create(['name' => 'Scarves', 'slug' => 'scarves', 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Silk Scarf', 'slug' => 'silk-scarf', 'category_id' => $category->id, 'product_code' => 'SS-1',
            'purchase_price' => 50, 'new_price' => 500, 'stock' => 10, 'status' => 1, 'approval_status' => 'approved',
        ]);
        app(\App\Services\StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 50]);
        $this->area = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
    }

    private function order(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('customer.ordersave'), array_merge([
            'product' => $this->product->id,
            'name' => 'Rahim',
            'phone' => '01744444444',
            'address' => 'Mirpur, Dhaka',
            'area' => $this->area->id,
        ], $extra));
    }

    public function test_the_chosen_variant_sets_the_price_and_the_cart_option(): void
    {
        $variant = ProductVariantPrice::create(['product_id' => $this->product->id, 'price' => 650, 'stock' => 4, 'sku' => 'SS-R']);

        $this->order(['variant' => $variant->id]);

        $line = \App\Models\OrderDetails::where('product_id', $this->product->id)->firstOrFail();
        $this->assertSame($variant->id, (int) $line->variant_price_id);
        $this->assertEquals(650, (float) $line->sale_price);
    }

    public function test_an_out_of_stock_variant_is_refused(): void
    {
        $variant = ProductVariantPrice::create(['product_id' => $this->product->id, 'price' => 650, 'stock' => 0, 'sku' => 'SS-R']);

        $this->order(['variant' => $variant->id]);

        $this->assertCount(0, Cart::instance('shopping')->content());
    }

    public function test_a_product_with_options_needs_one_to_be_chosen(): void
    {
        ProductVariantPrice::create(['product_id' => $this->product->id, 'price' => 650, 'stock' => 4, 'sku' => 'SS-R']);

        $this->order();

        $this->assertCount(0, Cart::instance('shopping')->content());
    }

    public function test_a_variant_of_another_product_is_refused(): void
    {
        $other = Product::create([
            'name' => 'Other', 'slug' => 'other', 'category_id' => $this->product->category_id, 'product_code' => 'OT-1',
            'purchase_price' => 5, 'new_price' => 9, 'stock' => 0, 'status' => 1, 'approval_status' => 'approved',
        ]);
        $foreign = ProductVariantPrice::create(['product_id' => $other->id, 'price' => 1, 'stock' => 9, 'sku' => 'OT-R']);

        $this->order(['variant' => $foreign->id]);

        $this->assertCount(0, Cart::instance('shopping')->content());
    }

    public function test_a_product_without_options_orders_at_its_own_price(): void
    {
        $this->order();

        $line = \App\Models\OrderDetails::where('product_id', $this->product->id)->firstOrFail();
        $this->assertEquals(500, (float) $line->sale_price);
        $this->assertNull($line->variant_price_id);
    }
}
