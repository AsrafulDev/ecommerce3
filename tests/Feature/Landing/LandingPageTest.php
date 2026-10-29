<?php

namespace Tests\Feature\Landing;

use App\Models\GeneralSetting;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The public luxury landing page: sections, switches, colours, validity and output safety.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);

        // An installed site has a settings row; the layout reads it once at boot, so share it here too.
        $settings = GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);
        Cache::forget('general_setting');
        view()->share('generalsetting', $settings);
    }

    private function product(): Product
    {
        $category = \App\Models\Category::create(['name' => 'Scarves', 'slug' => 'scarves', 'status' => 1, 'parent_id' => 0]);

        return Product::create([
            'name' => 'Silk Scarf', 'slug' => 'silk-scarf', 'category_id' => $category->id, 'product_code' => 'SS-1',
            'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1, 'approval_status' => 'approved',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function landing(array $overrides = []): LandingTemplate
    {
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        $content['elements']['hero.title'] = ['value' => 'Hand-woven elegance'];

        return LandingTemplate::create(array_merge([
            'name' => 'Silk Launch',
            'slug' => 'silk-launch',
            'template_key' => TemplateRegistry::LUXURY,
            'product_id' => $overrides['product_id'] ?? $this->product()->id,
            'content' => $content,
            'status' => true,
        ], $overrides));
    }

    public function test_the_luxury_page_shows_its_sections_and_the_checkout(): void
    {
        $this->landing();

        $this->get('/landing/silk-launch')
            ->assertOk()
            ->assertSee('Hand-woven elegance')
            ->assertSee('id="problem"', false)
            ->assertSee('id="solution"', false)
            ->assertSee('id="faq"', false)
            ->assertSee('id="checkout"', false)
            ->assertSee('name="phone"', false);
    }

    public function test_a_switched_off_section_is_not_shown_and_the_checkout_still_is(): void
    {
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        $content['sections']['faq']['enabled'] = false;
        $this->landing(['content' => $content]);

        $this->get('/landing/silk-launch')
            ->assertOk()
            ->assertDontSee('id="faq"', false)
            ->assertSee('id="checkout"', false);
    }

    public function test_an_unpublished_landing_is_not_found(): void
    {
        $this->landing(['status' => false]);

        $this->get('/landing/silk-launch')->assertNotFound();
    }

    public function test_outside_the_validity_window_the_checkout_is_replaced_by_a_closed_notice(): void
    {
        $this->landing(['valid_until' => now()->subDay()]);

        $this->get('/landing/silk-launch')
            ->assertOk()
            ->assertSee('This offer is closed')
            ->assertDontSee('name="phone"', false);
    }

    public function test_the_colours_from_the_content_are_used(): void
    {
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        $content['theme']['primary'] = '#123456';
        $this->landing(['content' => $content]);

        $this->get('/landing/silk-launch')->assertSee('--primary: #123456', false);
    }

    public function test_rich_text_is_cleaned_on_the_way_out(): void
    {
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        // Stored as if written directly to the database, bypassing the validator.
        $content['elements']['hero.subtitle'] = ['value' => '<img src=x onerror=alert(1)><b onclick="x">Bold</b>'];
        $this->landing(['content' => $content]);

        $this->get('/landing/silk-launch')
            ->assertOk()
            ->assertDontSee('onerror', false)
            ->assertDontSee('onclick', false)
            ->assertSee('<b>Bold</b>', false);
    }

    public function test_the_page_lists_the_variants_with_their_prices(): void
    {
        $product = $this->product();
        $this->landing(['product_id' => $product->id]);
        \App\Models\ProductVariantPrice::create(['product_id' => $product->id, 'price' => 650, 'stock' => 4, 'sku' => 'SS-R']);

        $this->get('/landing/silk-launch')
            ->assertOk()
            ->assertSee('name="variant"', false)
            ->assertSee('৳650.00');
    }

    public function test_checkout_labels_and_placeholders_show_the_saved_text(): void
    {
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        $content['elements']['checkout.label_name'] = ['value' => 'Full name'];
        $content['elements']['checkout.placeholder_phone'] = ['value' => 'Mobile for delivery'];
        $this->landing(['content' => $content]);

        $this->get('/landing/silk-launch')
            ->assertOk()
            ->assertSee('Full name')
            ->assertSee('placeholder="Mobile for delivery"', false)
            ->assertSee('placeholder="Your name"', false);
    }
}
