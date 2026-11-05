<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\InvalidLandingContent;
use App\Services\Landing\TemplateRegistry;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The luxury template has the same header as the others: the store logo or brand text on the left, and the order
 * button on the right. The header stays on, like Fashion's and Grocery's. The footer is the shared one.
 */
class LuxuryHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function settings(?string $darkLogo): void
    {
        $settings = GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => $darkLogo ?? '',
            'favicon' => 'public/favicon.png', 'status' => 1, 'copyright' => 'Test Shop · All rights reserved',
        ]);
        Cache::forget('general_setting');
        view()->share('generalsetting', $settings);
    }

    private function luxury(array $elements = []): LandingTemplate
    {
        $category = Category::create(['name' => 'Luxury header', 'slug' => 'lh-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Silk scarf', 'slug' => 'lh-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'LH-'.uniqid(), 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        $content = array_replace_recursive($content, ['elements' => $elements]);

        return LandingTemplate::create([
            'name' => 'Luxury page', 'slug' => 'lh-page-'.uniqid(), 'template_key' => TemplateRegistry::LUXURY,
            'product_id' => $product->id, 'content' => $content, 'status' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
    }

    public function test_the_luxury_page_shows_the_header_with_its_brand_and_order_button(): void
    {
        $this->settings(null);
        $landing = $this->luxury();

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('class="lx-nav"', false)
            ->assertSee('class="lx-brand"', false)
            ->assertSee('আপনার ব্র্যান্ড')
            ->assertSee('LUXURY COLLECTION')
            ->assertSee('class="lx-nav-cta" href="#checkout"', false);
    }

    public function test_the_store_logo_takes_the_place_of_the_brand_text_in_the_header(): void
    {
        $this->settings('public/logo.png');
        $landing = $this->luxury();

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('class="lx-logo"', false)
            ->assertDontSee('আপনার ব্র্যান্ড');
    }

    public function test_an_edited_brand_shows_in_the_header(): void
    {
        $this->settings(null);
        $landing = $this->luxury(['nav.brand' => ['value' => 'Maison Aurel']]);

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('Maison Aurel');
    }

    public function test_the_header_cannot_be_switched_off(): void
    {
        $content = TemplateRegistry::defaultContent(TemplateRegistry::LUXURY);
        $content['sections']['nav'] = ['enabled' => false];

        try {
            ContentValidator::clean($content, TemplateRegistry::LUXURY);
            $this->fail('the header must not be switchable off');
        } catch (InvalidLandingContent $e) {
            $this->assertContains('The header section cannot be switched off.', $e->errors);
        }
    }

    public function test_the_editor_offers_the_header_fields(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $this->settings(null);
        $landing = $this->luxury();

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="nav.brand"', false)
            ->assertSee('data-el="nav.brand_sub"', false)
            ->assertSee('data-el="nav.cta"', false);
    }

    public function test_the_luxury_page_has_the_footer_with_the_copyright_text(): void
    {
        $this->settings(null);
        $landing = $this->luxury();

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('<footer class="lp-foot">', false)
            ->assertSee('Test Shop · All rights reserved');
    }
}
