<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\IncompleteOrder;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\ShippingCharge;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A landing order that was filled in but not placed is kept, like the shop's incomplete orders:
 * the price, delivery charge and discount come from the database, and a closed page keeps nothing.
 */
class LandingIncompleteOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private LandingTemplate $landing;

    private ShippingCharge $area;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);

        GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);

        $category = Category::create(['name' => 'Scarves', 'slug' => 'scarves', 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Silk Scarf', 'slug' => 'silk-scarf', 'category_id' => $category->id, 'product_code' => 'SS-1',
            'purchase_price' => 50, 'new_price' => 500, 'stock' => 10, 'status' => 1, 'approval_status' => 'approved',
        ]);
        $this->area = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);

        $this->landing = $this->landingPage();
    }

    private function landingPage(bool $status = true): LandingTemplate
    {
        return LandingTemplate::create([
            'name' => 'Silk launch', 'slug' => 'silk-launch', 'template_key' => TemplateRegistry::LUXURY,
            'product_id' => $this->product->id,
            'content' => ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY),
            'status' => $status,
        ]);
    }

    private function save(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('landing.incomplete'), array_merge([
            'landing' => $this->landing->id,
            'product' => $this->product->id,
            'name' => 'Rahim',
            'phone' => '01744444444',
            'address' => 'Mirpur, Dhaka',
        ], $extra));
    }

    public function test_a_filled_in_landing_order_is_kept_with_its_option_and_total(): void
    {
        $variant = ProductVariantPrice::create(['product_id' => $this->product->id, 'price' => 650, 'stock' => 4, 'sku' => 'SS-R']);

        $this->save(['variant' => $variant->id, 'quantity' => 2, 'area' => $this->area->id])->assertOk();

        $saved = IncompleteOrder::firstOrFail();
        $this->assertSame('Rahim', $saved->name);
        $this->assertSame(1, count($saved->items));
        $this->assertSame($variant->id, (int) $saved->items[0]['variant_price_id']);
        $this->assertSame(2, (int) $saved->items[0]['qty']);
        $this->assertEquals(650, (float) $saved->items[0]['price']);
        $this->assertEquals(1360, (float) $saved->total_amount); // 2 × 650 + 60 delivery
        $this->assertStringContainsString('/landing/silk-launch', (string) $saved->product_link);
    }

    public function test_the_price_is_taken_from_the_database_not_the_page(): void
    {
        $this->save(['quantity' => 1, 'area' => $this->area->id])->assertOk();

        $saved = IncompleteOrder::firstOrFail();
        $this->assertEquals(560, (float) $saved->total_amount); // product price 500 + delivery 60
    }

    public function test_an_unfinished_form_keeps_nothing(): void
    {
        $this->save(['address' => ''])->assertStatus(422);

        $this->assertSame(0, IncompleteOrder::count());
    }

    public function test_a_closed_landing_page_keeps_nothing(): void
    {
        $this->landing->update(['status' => false]);

        $this->save()->assertStatus(422);

        $this->assertSame(0, IncompleteOrder::count());
    }

    public function test_the_same_visitor_keeps_one_row_with_every_attempt(): void
    {
        // The test client cannot pin one session across requests, so the visitor's session is set here.
        $session = new \Illuminate\Session\Store('test_session', new \Illuminate\Session\ArraySessionHandler(120));
        $session->setId(hash('sha1', 'landing-lead-same-visitor'));
        $session->start();

        foreach (['01744444444', '01755555555'] as $phone) {
            $request = \Illuminate\Http\Request::create(route('landing.incomplete'), 'POST', [
                'landing' => $this->landing->id,
                'product' => $this->product->id,
                'name' => 'Rahim',
                'phone' => $phone,
                'address' => 'Mirpur, Dhaka',
            ]);
            $request->setLaravelSession($session);

            $response = app(\App\Http\Controllers\Frontend\LandingController::class)
                ->incompleteOrder($request, app(\App\Services\IncompleteOrderRecorder::class));
            $this->assertSame(200, $response->getStatusCode());
        }

        $this->assertSame(1, IncompleteOrder::count());
        $this->assertCount(2, IncompleteOrder::firstOrFail()->attempts);
    }
}
