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
 * The Fashion product details section: its spec table can be added to and removed from in the editor, a table with no
 * rows is left off the live page, and a photo that was removed stays removed.
 */
class FashionDetailsTest extends TestCase
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

    private function fashion(array $elements = []): LandingTemplate
    {
        $category = Category::create(['name' => 'Fashion details', 'slug' => 'fd-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Embroidered set', 'slug' => 'fd-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'FD-'.uniqid(), 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::FASHION), TemplateRegistry::FASHION);
        $content = array_replace_recursive($content, ['elements' => $elements]);

        return LandingTemplate::create([
            'name' => 'Fashion details page', 'slug' => 'fd-page-'.uniqid(), 'template_key' => TemplateRegistry::FASHION,
            'product_id' => $product->id, 'content' => $content, 'status' => true,
        ]);
    }

    /** Every spec row of the details section, emptied: label and value both blank. */
    private function allRowsRemoved(): array
    {
        $rows = [];
        for ($n = 1; $n <= 8; $n++) {
            $rows["details.row.{$n}.label"] = ['value' => ''];
            $rows["details.row.{$n}.value"] = ['value' => ''];
        }

        return $rows;
    }

    /** The product details section only, so counts are not mixed up with the other sections' lists. */
    private function detailsSection(string $html): string
    {
        $start = strpos($html, 'id="details"');
        $this->assertNotFalse($start, 'the details section is on the page');
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_the_live_page_shows_the_spec_table_with_its_rows(): void
    {
        $landing = $this->fashion();

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('class="fx-spec"', false)
            ->assertSee('কাপড়')
            ->assertSee('৬০% লিনেন, ৪০% কটন');
    }

    public function test_removing_every_row_hides_the_table_on_the_live_page(): void
    {
        $landing = $this->fashion($this->allRowsRemoved());

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertDontSee('class="fx-spec"', false)
            ->assertSee('id="details"', false);
    }

    public function test_the_editor_keeps_the_table_visible_when_every_row_is_removed_so_a_row_can_be_added(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->fashion($this->allRowsRemoved());

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('class="fx-spec"', false)
            ->assertSee('data-list="details.row"', false)
            ->assertSee('data-el="details.row.1.label"', false);
    }

    public function test_the_editor_offers_a_remove_control_on_each_filled_row_and_an_add_button_under_the_table(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->fashion();

        $details = $this->detailsSection($this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-list="details.row"', false)
            ->getContent());

        // The default content has six filled rows, so six remove controls, and the add button sits under the table.
        $this->assertSame(6, substr_count($details, 'class="lp-row-remove"'));
        $this->assertStringContainsString('data-list="details.row"', $details);
        $this->assertStringContainsString('lp-row-add', $details);
    }

    public function test_the_editor_offers_remove_and_change_for_both_photos_in_the_details_section(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->fashion();

        $details = $this->detailsSection($this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="details.image"', false)
            ->assertSee('data-el="details.image_2"', false)
            ->getContent());

        $this->assertSame(2, substr_count($details, 'lp-img-remove'));
        $this->assertSame(2, substr_count($details, 'lp-img-change'));
    }

    public function test_a_second_photo_removed_in_the_editor_stays_off_the_live_page(): void
    {
        $landing = $this->fashion(['details.image_2' => ['value' => '']]);

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertDontSee('class="fx-photo fx-photo-wide"', false)
            ->assertSee('class="fx-spec"', false);
    }

    public function test_a_main_photo_removed_in_the_editor_is_not_replaced_by_the_product_photo(): void
    {
        // The product in the test has no photo, so the main photo has nothing to show once it is cleared.
        $landing = $this->fashion(['details.image' => ['value' => '']]);

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertDontSee('class="fx-photo"', false);
    }

    public function test_without_a_main_photo_on_the_live_page_the_details_text_takes_the_full_width(): void
    {
        $landing = $this->fashion(['details.image' => ['value' => '']]);

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('fx-split fx-split-solo', false);
    }

    public function test_the_product_colour_items_are_not_part_of_the_details_section(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $landing = $this->fashion();

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertDontSee('class="fx-colours"', false)
            ->assertDontSee('class="fx-chip"', false);

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertDontSee('data-el="details.colour.1.name"', false);

        $this->assertArrayNotHasKey('colour', TemplateRegistry::schema(TemplateRegistry::FASHION)['sections']['details']['lists']);
    }

    public function test_the_live_table_shows_only_the_filled_rows(): void
    {
        $elements = $this->allRowsRemoved();
        $elements['details.row.2.label'] = ['value' => 'দোপাট্টা'];
        $elements['details.row.2.value'] = ['value' => 'সফট অরগাঞ্জা'];
        $landing = $this->fashion($elements);

        $details = $this->detailsSection($this->get('/landing/'.$landing->slug)->assertOk()->assertSee('দোপাট্টা')->getContent());

        $this->assertSame(1, substr_count($details, '<tr class="'), 'only the one filled row is printed');
    }
}
