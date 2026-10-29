<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Services\Landing\LandingPricing;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPricingTest extends TestCase
{
    use RefreshDatabase;

    private ?Product $product = null;

    private function landing(array $overrides = []): LandingTemplate
    {
        $this->product ??= Product::create([
            'name' => 'Priced', 'slug' => 'priced',
            'category_id' => Category::create(['name' => 'Pricing', 'slug' => 'pricing', 'status' => 1, 'parent_id' => 0])->id,
            'product_code' => 'PR-1', 'purchase_price' => 1, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        return LandingTemplate::create(array_merge([
            'name' => 'Pricing Landing', 'slug' => 'pricing-landing-' . uniqid(), 'template_key' => TemplateRegistry::LUXURY,
            'product_id' => $this->product->id, 'status' => true,
        ], $overrides));
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'SHOP10', 'type' => 'percent', 'value' => 10, 'min_purchase' => 0,
            'max_uses' => 0, 'used_count' => 0, 'status' => 1,
        ], $overrides));
    }

    public function test_an_inactive_landing_is_closed(): void
    {
        $this->assertFalse(LandingPricing::isOpen($this->landing(['status' => false])));
    }

    public function test_a_landing_is_closed_before_and_after_its_window(): void
    {
        $this->assertFalse(LandingPricing::isOpen($this->landing(['valid_from' => now()->addDay()])));
        $this->assertFalse(LandingPricing::isOpen($this->landing(['slug' => 'ended', 'valid_until' => now()->subDay()])));
    }

    public function test_a_landing_is_open_inside_its_window(): void
    {
        $landing = $this->landing(['valid_from' => now()->subDay(), 'valid_until' => now()->addDay()]);

        $this->assertTrue(LandingPricing::isOpen($landing));
    }

    public function test_an_extra_percent_discount_and_a_capped_fixed_discount(): void
    {
        $percent = $this->landing(['extra_discount_type' => 'percent', 'extra_discount_value' => 10]);
        $this->assertSame(50.0, LandingPricing::extraDiscount($percent, 500));

        $fixed = $this->landing(['slug' => 'fixed', 'extra_discount_type' => 'fixed', 'extra_discount_value' => 800]);
        $this->assertSame(500.0, LandingPricing::extraDiscount($fixed, 500), 'never more than the line');
    }

    public function test_the_larger_of_the_extra_discount_and_the_coupon_applies(): void
    {
        $coupon = $this->coupon(['value' => 20]);
        $landing = $this->landing(['coupon_id' => $coupon->id, 'extra_discount_type' => 'fixed', 'extra_discount_value' => 50]);

        $best = LandingPricing::best($landing, 500);
        $this->assertSame($coupon->id, $best['coupon']->id, 'a 20% coupon (100) beats a 50 discount');
        $this->assertSame(100.0, $best['discount']);

        $landing->update(['extra_discount_type' => 'fixed', 'extra_discount_value' => 150]);
        $best = LandingPricing::best($landing->fresh(), 500);
        $this->assertNull($best['coupon'], 'a 150 discount beats the 100 coupon');
        $this->assertSame(150.0, $best['discount']);
    }

    public function test_an_expired_coupon_gives_no_discount(): void
    {
        $coupon = $this->coupon(['valid_to' => now()->subDay()]);

        $this->assertSame(0.0, LandingPricing::couponDiscount($coupon->fresh(), 500));
    }

    public function test_a_used_up_coupon_gives_no_discount(): void
    {
        $coupon = $this->coupon(['max_uses' => 2, 'used_count' => 2]);

        $this->assertSame(0.0, LandingPricing::couponDiscount($coupon->fresh(), 500));
    }

    public function test_a_coupon_below_its_minimum_purchase_gives_no_discount(): void
    {
        $coupon = $this->coupon(['min_purchase' => 1000]);

        $this->assertSame(0.0, LandingPricing::couponDiscount($coupon->fresh(), 500));
    }

    public function test_issuing_a_coupon_links_it_and_limits_it_to_the_landing_window(): void
    {
        $landing = $this->landing(['valid_from' => now()->subDay(), 'valid_until' => now()->addWeek()]);

        $coupon = LandingPricing::issueCoupon($landing, 'percent', 15);

        $this->assertStringStartsWith('LP', $coupon->code);
        $this->assertSame($coupon->id, $landing->fresh()->coupon_id);
        $this->assertSame($landing->valid_until->toDateString(), $coupon->valid_to->toDateString());
        $this->assertSame(15.0, (float) $coupon->value);
    }
}
