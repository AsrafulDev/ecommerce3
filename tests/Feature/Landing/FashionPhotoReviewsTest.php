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
 * Fashion photo reviews: on the live page only the photos show, four at a time on a desktop, and a row of more than four
 * slides on by itself. The editor shows every photo slot.
 */
class FashionPhotoReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);

        $settings = GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => '',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);
        Cache::forget('general_setting');
        view()->share('generalsetting', $settings);
    }

    /** @param  list<string>  $images  one photo review per image, in order */
    private function fashion(array $images = [], array $extra = []): LandingTemplate
    {
        $category = Category::create(['name' => 'Photo reviews', 'slug' => 'pr-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Photo review item', 'slug' => 'pr-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'PR-'.uniqid(), 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        $elements = [];
        foreach ($images as $n => $image) {
            $elements['reviews.photo.'.($n + 1).'.image'] = ['value' => $image];
            // A name and a rating are stored too, to show that the live page does not print them.
            $elements['reviews.photo.'.($n + 1).'.name'] = ['value' => 'Photo person '.($n + 1)];
            $elements['reviews.photo.'.($n + 1).'.rating'] = ['value' => '5'];
        }
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::FASHION), TemplateRegistry::FASHION);
        $content = array_replace_recursive($content, ['elements' => array_merge($elements, $extra)]);

        return LandingTemplate::create([
            'name' => 'Fashion photos', 'slug' => 'fp-page-'.uniqid(), 'template_key' => TemplateRegistry::FASHION,
            'product_id' => $product->id, 'content' => $content, 'status' => true,
        ]);
    }

    private function photos(int $count): array
    {
        return array_map(fn (int $n) => "public/uploads/review-photo-{$n}.webp", range(1, $count));
    }

    /** The photo reviews part of the page only, so counts are not mixed up with the text reviews. */
    private function photoSection(string $html): string
    {
        $start = strpos($html, 'Photo reviews');
        $this->assertNotFalse($start, 'the photo reviews heading is on the page');
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_the_live_photo_reviews_show_the_photos_and_nothing_else(): void
    {
        $landing = $this->fashion($this->photos(2));

        $html = $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('data-photo-slider', false)
            ->getContent();

        $photos = $this->photoSection($html);
        $this->assertSame(2, substr_count($photos, '<figure class="fx-photo-slide">'));
        $this->assertStringNotContainsString('class="fx-photo-review', $photos, 'no card around a photo');
        $this->assertStringNotContainsString('<figcaption', $photos, 'no caption under a photo');
        $this->assertStringNotContainsString('Photo person 1', $photos, 'no name is printed with a photo');
        $this->assertStringNotContainsString('lp-star', $photos, 'no stars are printed with a photo');
    }

    public function test_up_to_four_photos_are_shown_fixed_and_more_than_four_are_set_to_slide(): void
    {
        $four = $this->fashion($this->photos(4));
        $html = $this->get('/landing/'.$four->slug)->assertOk()->assertSee('data-count="4"', false)->getContent();
        $this->assertSame(4, substr_count($this->photoSection($html), '<figure class="fx-photo-slide">'));

        $five = $this->fashion($this->photos(5));
        $html = $this->get('/landing/'.$five->slug)->assertOk()->assertSee('data-count="5"', false)->getContent();
        $this->assertSame(5, substr_count($this->photoSection($html), '<figure class="fx-photo-slide">'));
        $this->assertStringContainsString('setInterval(next, 4000)', $html, 'the slider moves on by itself');
        $this->assertStringContainsString('prefers-reduced-motion', $html, 'the slider stays still for people who ask it to');
    }

    public function test_without_photos_there_is_no_photo_slider(): void
    {
        $landing = $this->fashion([]);

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertDontSee('data-photo-slider', false);
    }

    public function test_the_editor_shows_every_photo_slot_with_change_remove_and_add(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->fashion($this->photos(1));

        $html = $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="reviews.photo.1.image"', false)
            ->assertSee('data-list="reviews.photo"', false)
            ->getContent();

        $photos = $this->photoSection($html);
        $this->assertStringContainsString('class="fx-photo-review', $photos, 'the editor draws each photo slot');
        $this->assertStringContainsString('lp-row-remove', $photos);
        $this->assertStringContainsString('lp-row-add', $photos);
        $this->assertStringNotContainsString('data-photo-slider', $html, 'the editor does not slide the photos');
    }

    public function test_the_editor_does_not_offer_a_name_or_stars_for_a_photo(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->fashion($this->photos(1));

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertDontSee('data-el="reviews.photo.1.name"', false)
            ->assertDontSee('data-el="reviews.photo.1.rating"', false);
    }
}
