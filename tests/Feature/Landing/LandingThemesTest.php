<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\LandingThemes;
use App\Services\Landing\TemplateRegistry;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing editor's theme presets: each one is four valid colours, the editor lists them, and saving one stores its
 * colours, which the live page then uses.
 */
class LandingThemesTest extends TestCase
{
    use RefreshDatabase;

    private function landing(): LandingTemplate
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');

        $category = Category::create(['name' => 'Themes', 'slug' => 'themes-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Themed item', 'slug' => 'themed-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'TH-'.uniqid(), 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        return LandingTemplate::create([
            'name' => 'Themed page', 'slug' => 'themed-page-'.uniqid(), 'template_key' => TemplateRegistry::LUXURY,
            'product_id' => $product->id,
            'content' => ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY),
            'status' => true,
        ]);
    }

    public function test_every_preset_has_four_valid_colours_and_a_unique_label(): void
    {
        $presets = LandingThemes::presets();
        $this->assertNotEmpty($presets);

        $labels = [];
        foreach ($presets as $key => $preset) {
            $this->assertSame(['primary', 'accent', 'background', 'text'], array_keys($preset['colours']), "preset {$key}");
            foreach ($preset['colours'] as $name => $hex) {
                $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $hex, "preset {$key} colour {$name}");
            }
            $labels[] = $preset['label'];
        }

        $this->assertCount(count($labels), array_unique($labels), 'every preset has its own name');
    }

    public function test_the_editor_lists_every_preset_under_the_theme_panel(): void
    {
        $landing = $this->landing();

        $response = $this->get(route('landing.manage.edit', $landing->id))->assertOk()
            ->assertSee('Customise colours');

        foreach (array_keys(LandingThemes::presets()) as $key) {
            $response->assertSee('data-preset="'.$key.'"', false);
        }
        foreach (LandingThemes::presets() as $preset) {
            $response->assertSee($preset['label']);
        }
    }

    public function test_saving_a_preset_stores_its_colours_and_the_live_page_uses_them(): void
    {
        $landing = $this->landing();
        $midnight = LandingThemes::presets()['midnight']['colours'];

        $this->postJson(route('landing.manage.save', $landing->id), ['theme' => $midnight])
            ->assertOk()
            ->assertJsonPath('ok', true);

        foreach ($midnight as $key => $hex) {
            $this->assertSame($hex, $landing->fresh()->content['theme'][$key]);
        }

        $this->get('/landing/'.$landing->slug)
            ->assertOk()
            ->assertSee('--primary: '.$midnight['primary'], false)
            ->assertSee('--bg: '.$midnight['background'], false);
    }
}
