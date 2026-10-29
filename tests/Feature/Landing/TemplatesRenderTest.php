<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\TemplateRegistry;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Every template renders its default content on the public page and in the editor frame,
 * and the create popup offers all of them.
 */
class TemplatesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);

        $settings = GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);
        Cache::forget('general_setting');
        view()->share('generalsetting', $settings);
    }

    private function landing(string $key, array $content = []): LandingTemplate
    {
        $category = Category::create(['name' => 'Tpl ' . $key, 'slug' => 'tpl-' . $key, 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Item ' . $key, 'slug' => 'item-' . $key, 'category_id' => $category->id,
            'product_code' => 'T-' . $key, 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        $base = ContentValidator::clean(TemplateRegistry::defaultContent($key), $key);
        $base = array_replace_recursive($base, $content);

        return LandingTemplate::create([
            'name' => 'Page ' . $key, 'slug' => 'page-' . $key, 'template_key' => $key,
            'product_id' => $product->id, 'content' => $base, 'status' => true,
        ]);
    }

    public function test_each_template_renders_its_default_page_with_the_checkout(): void
    {
        foreach ([TemplateRegistry::LUXURY, TemplateRegistry::FASHION, TemplateRegistry::GADGET] as $key) {
            $landing = $this->landing($key);

            $this->get('/landing/' . $landing->slug)
                ->assertOk()
                ->assertSee('id="checkout"', false)
                ->assertSee('name="phone"', false)
                ->assertSee(TemplateRegistry::defaultContent($key)['elements']['hero.title']['value']);
        }
    }

    public function test_fashion_and_gadget_show_their_own_sections(): void
    {
        $this->landing(TemplateRegistry::FASHION);
        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertDontSee('id="sizes"', false)
            ->assertSee('id="details"', false)
            ->assertSee('id="offer"', false)
            ->assertSee('id="final"', false);

        $this->landing(TemplateRegistry::GADGET);
        $this->get('/landing/page-gadget')
            ->assertOk()
            ->assertSee('id="specs"', false)
            ->assertSee('Specification')
            ->assertSee('id="compare"', false)
            ->assertSee('id="final"', false);
    }

    public function test_a_switched_off_gadget_section_is_not_shown_and_the_checkout_still_is(): void
    {
        $this->landing(TemplateRegistry::GADGET, ['sections' => ['specs' => ['enabled' => false]]]);

        $this->get('/landing/page-gadget')
            ->assertOk()
            ->assertDontSee('id="specs"', false)
            ->assertSee('id="checkout"', false);
    }

    public function test_the_editor_frame_is_editable_for_each_template(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');

        foreach ([TemplateRegistry::FASHION, TemplateRegistry::GADGET] as $key) {
            $landing = $this->landing($key);

            $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
                ->assertOk()
                ->assertSee('data-el="hero.title"', false)
                ->assertSee('contenteditable="true"', false)
                ->assertSee('data-el="checkout.label_name"', false);
        }
    }

    public function test_the_create_popup_offers_all_three_templates(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');

        $this->get(route('landing.manage.index'))
            ->assertOk()
            ->assertSee('value="luxury"', false)
            ->assertSee('value="fashion"', false)
            ->assertSee('value="gadget"', false);
    }

    public function test_an_empty_field_is_hidden_on_the_live_page(): void
    {
        $this->landing(TemplateRegistry::FASHION, ['elements' => ['hero.tagline' => ['value' => '']]]);

        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertDontSee('class="fx-tagline"', false)
            ->assertSee('class="fx-title"', false);
    }

    public function test_a_section_with_nothing_in_it_is_left_out_of_the_live_page(): void
    {
        $content = [
            'elements' => [
                'benefits.heading' => ['value' => ''],
                'benefits.eyebrow' => ['value' => ''],
            ],
        ];
        $content['elements']['benefits.item.1.title'] = ['value' => ''];
        $content['elements']['benefits.item.1.text'] = ['value' => ''];
        $landing = $this->landing(TemplateRegistry::FASHION, ['elements' => $content['elements']]);
        $stored = $landing->content;
        foreach (array_keys($stored['elements']) as $id) {
            if (str_starts_with($id, 'benefits.')) {
                $stored['elements'][$id] = ['value' => ''];
            }
        }
        $landing->update(['content' => $stored]);

        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertDontSee('id="benefits"', false);
    }

    public function test_the_editor_still_shows_empty_fields(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->landing(TemplateRegistry::FASHION, ['elements' => ['hero.tagline' => ['value' => '']]]);

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('class="fx-tagline"', false)
            ->assertSee('data-el="hero.tagline"', false);
    }

    public function test_the_final_banner_and_offer_photo_can_be_changed_in_the_editor(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->landing(TemplateRegistry::FASHION);

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="final.image"', false)
            ->assertSee('data-bg="1"', false)
            ->assertSee('data-el="offer.image"', false)
            ->assertSee('Change background')
            ->assertSee('Change image');
    }

    public function test_a_saved_banner_background_and_offer_photo_show_on_the_live_page(): void
    {
        $landing = $this->landing(TemplateRegistry::FASHION, ['elements' => [
            'final.image' => ['value' => 'public/uploads/final-banner.webp'],
            'offer.image' => ['value' => 'public/uploads/offer-photo.webp'],
        ]]);

        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertSee('final-banner.webp', false)
            ->assertSee('offer-photo.webp', false);
    }

    public function test_reviews_have_an_editable_star_rating_and_photo_reviews_in_the_editor(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->landing(TemplateRegistry::FASHION, ['elements' => [
            'reviews.item.1.name' => ['value' => 'Nusrat'],
            'reviews.item.1.rating' => ['value' => '4'],
            'reviews.item.1.text' => ['value' => 'Soft and light.'],
            'reviews.photo.1.name' => ['value' => 'Rahim'],
            'reviews.photo.1.rating' => ['value' => '5'],
            'reviews.photo.1.image' => ['value' => 'public/uploads/review-photo.webp'],
        ]]);

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="reviews.item.1.rating"', false)
            ->assertSee('class="lp-star is-on"', false)
            ->assertSee('data-el="reviews.photo.1.image"', false)
            ->assertSee('data-el="reviews.photo.1.rating"', false);
    }

    public function test_a_photo_review_and_its_stars_show_on_the_live_page(): void
    {
        $this->landing(TemplateRegistry::FASHION, ['elements' => [
            'reviews.photo.1.name' => ['value' => 'Rahim'],
            'reviews.photo.1.rating' => ['value' => '5'],
            'reviews.photo.1.image' => ['value' => 'public/uploads/review-photo.webp'],
        ]]);

        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertSee('review-photo.webp', false)
            ->assertSee('★★★★★', false)
            ->assertDontSee('lp-star-edit', false);
    }

    public function test_the_gallery_grows_with_the_photos_added(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $elements = [];
        foreach ([1, 2, 3, 7] as $n) {
            $elements["gallery.item.{$n}.image"] = ['value' => "public/uploads/shot-{$n}.webp"];
        }
        $landing = $this->landing(TemplateRegistry::FASHION, ['elements' => $elements]);

        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertSee('shot-1.webp', false)
            ->assertSee('shot-7.webp', false)
            ->assertDontSee('>Front<', false);

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="gallery.item.24.image"', false);
    }

    public function test_an_empty_gallery_is_not_shown_on_the_live_page(): void
    {
        $this->landing(TemplateRegistry::FASHION, ['elements' => [
            'gallery.heading' => ['value' => ''],
            'gallery.eyebrow' => ['value' => ''],
        ]]);

        $this->get('/landing/page-fashion')
            ->assertOk()
            ->assertDontSee('id="gallery"', false);
    }
}
