<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\GeneralSetting;
use App\Models\LandingTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Services\Landing\LandingPricing;
use App\Services\Landing\TemplateRegistry;
use App\Services\StockManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A landing order applies the better of its extra discount and its coupon, refuses a closed
 * page, and refuses a landing that belongs to another product.
 */
class LandingOrderDiscountTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ShippingCharge $area;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);

        GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);

        $this->product = Product::create([
            'name' => 'Silk Scarf', 'slug' => 'silk-scarf',
            'category_id' => Category::create(['name' => 'Scarves', 'slug' => 'scarves', 'status' => 1, 'parent_id' => 0])->id,
            'product_code' => 'SS-1', 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 50]);
        $this->area = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
    }

    private function landing(array $overrides = []): LandingTemplate
    {
        return LandingTemplate::create(array_merge([
            'name' => 'Discount Landing', 'slug' => 'discount-landing-' . uniqid(),
            'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id, 'status' => true,
        ], $overrides));
    }

    private function order(LandingTemplate $landing): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('customer.ordersave'), [
            'product' => $this->product->id,
            'landing' => $landing->id,
            'name' => 'Rahim',
            'phone' => '01744444444',
            'address' => 'Mirpur, Dhaka',
            'area' => $this->area->id,
        ]);
    }

    public function test_the_extra_discount_is_taken_off_the_order(): void
    {
        $landing = $this->landing(['extra_discount_type' => 'percent', 'extra_discount_value' => 10]);

        $this->order($landing);

        $this->assertSame(50.0, (float) Order::latest('id')->firstOrFail()->discount);
        $this->assertNull(Order::latest('id')->first()->coupon_code);
    }

    public function test_the_landing_coupon_is_used_and_counted_once(): void
    {
        $landing = $this->landing();
        $coupon = LandingPricing::issueCoupon($landing, 'percent', 20);

        $this->order($landing);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($coupon->code, $order->coupon_code);
        $this->assertSame(100.0, (float) $order->discount);
        $this->assertSame(1, (int) Coupon::find($coupon->id)->used_count);
    }

    public function test_a_closed_landing_takes_no_order(): void
    {
        $landing = $this->landing(['valid_until' => now()->subDay()]);

        $this->order($landing);

        $this->assertSame(0, Order::count());
    }

    public function test_a_landing_for_another_product_takes_no_order(): void
    {
        $other = Product::create([
            'name' => 'Other', 'slug' => 'other', 'category_id' => $this->product->category_id, 'product_code' => 'OT-1',
            'purchase_price' => 5, 'new_price' => 9, 'stock' => 0, 'status' => 1, 'approval_status' => 'approved',
        ]);
        $landing = $this->landing(['product_id' => $other->id]);

        $this->order($landing);

        $this->assertSame(0, Order::count());
    }

    public function test_a_coupon_for_a_landing_is_refused_in_the_shop(): void
    {
        $landing = $this->landing();
        $coupon = LandingPricing::issueCoupon($landing, 'percent', 20);

        $this->post(route('coupon.apply'), ['coupon_code' => $coupon->code])
            ->assertRedirect();

        $this->assertFalse(session()->has('coupon_code'));
    }
}
