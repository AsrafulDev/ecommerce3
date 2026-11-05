<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\LandingPage;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Removing a review in the editor clears every field of it. A review with all its fields empty is no longer a review,
 * so it does not come back on the next load.
 */
class ReviewRemovalTest extends TestCase
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

    private function landing(string $key, array $elements): LandingTemplate
    {
        $category = Category::create(['name' => 'Review '.$key, 'slug' => 'rv-'.$key.'-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Reviewed item', 'slug' => 'rv-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'RV-'.uniqid(), 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        $content = ContentValidator::clean(TemplateRegistry::defaultContent($key), $key);
        $content = array_replace_recursive($content, ['elements' => $elements]);

        return LandingTemplate::create([
            'name' => 'Review page '.$key, 'slug' => 'rv-page-'.$key.'-'.uniqid(), 'template_key' => $key,
            'product_id' => $product->id, 'content' => $content, 'status' => true,
        ]);
    }

    /** The text reviews the page reads back, as the live page sees them. */
    private function textReviews(LandingTemplate $landing): int
    {
        return count((new LandingPage($landing->fresh()))->items('reviews'));
    }

    /** The photo reviews the page reads back, as the live page sees them. */
    private function photoReviews(LandingTemplate $landing): int
    {
        return count((new LandingPage($landing->fresh()))->items('reviews', 'photo'));
    }

    public function test_a_text_review_with_every_field_cleared_is_gone_from_the_page(): void
    {
        $landing = $this->landing(TemplateRegistry::FASHION, [
            'reviews.item.1.name' => ['value' => 'Asha'],
            'reviews.item.1.text' => ['value' => 'Very good fit.'],
            'reviews.item.1.rating' => ['value' => '5'],
        ]);
        $before = $this->textReviews($landing);

        // What the editor saves when the row is removed: every field empty.
        $landing->update(['content' => array_replace_recursive($landing->content, ['elements' => [
            'reviews.item.1.name' => ['value' => ''],
            'reviews.item.1.text' => ['value' => ''],
            'reviews.item.1.rating' => ['value' => ''],
        ]])]);

        $this->assertSame($before - 1, $this->textReviews($landing));
    }

    public function test_a_photo_review_with_its_photo_and_fields_cleared_is_gone_from_the_page(): void
    {
        $landing = $this->landing(TemplateRegistry::FASHION, [
            'reviews.photo.1.image' => ['value' => 'public/uploads/review-photo.webp'],
            'reviews.photo.1.name' => ['value' => 'Rumana'],
            'reviews.photo.1.rating' => ['value' => '4'],
        ]);
        $before = $this->photoReviews($landing);

        $landing->update(['content' => array_replace_recursive($landing->content, ['elements' => [
            'reviews.photo.1.image' => ['value' => ''],
            'reviews.photo.1.name' => ['value' => ''],
            'reviews.photo.1.rating' => ['value' => ''],
        ]])]);

        $this->assertSame($before - 1, $this->photoReviews($landing));
    }

    public function test_the_editor_offers_a_remove_control_on_each_filled_review(): void
    {
        $this->seed(\Database\Seeders\DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
        $landing = $this->landing(TemplateRegistry::FASHION, [
            'reviews.item.1.name' => ['value' => 'Asha'],
            'reviews.item.1.text' => ['value' => 'Very good fit.'],
            'reviews.item.1.rating' => ['value' => '5'],
        ]);

        $html = $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="reviews.item.1.rating"', false)
            ->getContent();

        $start = strpos($html, 'id="reviews"');
        $section = substr($html, $start, strpos($html, '</section>', $start) - $start);
        $this->assertGreaterThanOrEqual(1, substr_count($section, 'class="lp-row-remove"'));
    }
}
