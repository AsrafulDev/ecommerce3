<?php

namespace Tests\Feature\Landing;

use App\Models\Category;
use App\Models\LandingTheme;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\LandingThemes;
use App\Services\Landing\TemplateRegistry;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The Landing Themes manager under the Landing Page menu: custom themes can be created, changed, copied and deleted,
 * built-in themes are copied and never changed, and the editor offers both. Pages keep the colours they were saved with.
 */
class LandingThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    private function theme(string $name = 'Brand dusk', array $overrides = []): LandingTheme
    {
        return LandingTheme::create(array_merge([
            'name' => $name, 'primary' => '#7a3e9d', 'accent' => '#1d1030', 'background' => '#fbf8ff', 'text' => '#1d1030',
        ], $overrides));
    }

    private function staff(array $permissions): User
    {
        $user = User::create([
            'name' => 'Staff '.uniqid(), 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $user->givePermissionTo(Permission::where('guard_name', 'admin')->whereIn('name', $permissions)->get());

        return $user;
    }

    private function landingWithTheme(string $name): LandingTemplate
    {
        $category = Category::create(['name' => 'Theme manager', 'slug' => 'tm-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Themed', 'slug' => 'tm-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'TM-'.uniqid(), 'purchase_price' => 50, 'new_price' => 500, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);
        $content['theme'] = ['primary' => '#7a3e9d', 'accent' => '#1d1030', 'background' => '#fbf8ff', 'text' => '#1d1030'];

        return LandingTemplate::create([
            'name' => $name, 'slug' => 'tm-page-'.uniqid(), 'template_key' => TemplateRegistry::LUXURY,
            'product_id' => $product->id, 'content' => $content, 'status' => true,
        ]);
    }

    public function test_the_landing_page_menu_links_to_the_theme_manager(): void
    {
        $this->get(route('landing.manage.index'))
            ->assertOk()
            ->assertSee('Landing Themes')
            ->assertSee(route('landing.themes.index'), false);
    }

    public function test_the_manager_lists_the_built_in_and_the_custom_themes(): void
    {
        $custom = $this->theme('Brand dusk');

        $response = $this->get(route('landing.themes.index'))->assertOk()->assertSee('Brand dusk');
        foreach (LandingThemes::presets() as $preset) {
            $response->assertSee($preset['label']);
        }
        $response->assertSee(route('landing.themes.edit', $custom->id), false);
    }

    public function test_a_new_theme_is_saved_with_lower_case_colours(): void
    {
        $this->post(route('landing.themes.store'), [
            'name' => 'Desert',
            'primary' => '#C0392B', 'accent' => '#2C1A14', 'background' => '#FFF8F0', 'text' => '#2C1A14',
        ])->assertRedirect(route('landing.themes.index'));

        $theme = LandingTheme::where('name', 'Desert')->firstOrFail();
        $this->assertSame(['primary' => '#c0392b', 'accent' => '#2c1a14', 'background' => '#fff8f0', 'text' => '#2c1a14'], $theme->colours());
    }

    public function test_a_colour_that_is_not_hex_is_refused(): void
    {
        $this->post(route('landing.themes.store'), [
            'name' => 'Broken', 'primary' => 'red', 'accent' => '#2c1a14', 'background' => '#fff8f0', 'text' => '#2c1a14',
        ])->assertSessionHasErrors('primary');

        $this->assertFalse(LandingTheme::where('name', 'Broken')->exists());
    }

    public function test_a_name_another_theme_uses_is_refused(): void
    {
        $this->theme('Taken');

        $this->post(route('landing.themes.store'), [
            'name' => 'Taken', 'primary' => '#111111', 'accent' => '#222222', 'background' => '#333333', 'text' => '#444444',
        ])->assertSessionHasErrors('name');
    }

    public function test_a_custom_theme_can_be_changed(): void
    {
        $theme = $this->theme('Brand dusk');

        $this->post(route('landing.themes.update', $theme->id), [
            'name' => 'Brand dusk', 'primary' => '#2a6f97', 'accent' => '#0b2233', 'background' => '#f4f9fc', 'text' => '#0b2233',
        ])->assertRedirect(route('landing.themes.index'));

        $this->assertSame('#2a6f97', $theme->fresh()->primary);
    }

    public function test_a_built_in_theme_is_copied_into_a_custom_theme_that_can_be_changed(): void
    {
        $this->post(route('landing.themes.copy'), ['source' => 'builtin-gold'])->assertRedirect();

        $copy = LandingTheme::where('name', 'Gold & ink (copy)')->firstOrFail();
        $this->assertSame(LandingThemes::presets()['gold']['colours'], $copy->colours());
    }

    public function test_copying_the_same_theme_again_gets_the_next_name(): void
    {
        $this->post(route('landing.themes.copy'), ['source' => 'builtin-gold']);
        $this->post(route('landing.themes.copy'), ['source' => 'builtin-gold']);

        $this->assertTrue(LandingTheme::where('name', 'Gold & ink (copy)')->exists());
        $this->assertTrue(LandingTheme::where('name', 'Gold & ink (copy) 2')->exists());
    }

    public function test_a_custom_theme_can_be_copied(): void
    {
        $theme = $this->theme('Brand dusk');

        $this->post(route('landing.themes.copy'), ['source' => 'custom-'.$theme->id])->assertRedirect();

        $this->assertTrue(LandingTheme::where('name', 'Brand dusk (copy)')->exists());
    }

    public function test_an_unknown_theme_cannot_be_copied(): void
    {
        $this->post(route('landing.themes.copy'), ['source' => 'builtin-nope'])->assertNotFound();
        $this->post(route('landing.themes.copy'), ['source' => 'custom-999'])->assertNotFound();
    }

    public function test_a_custom_theme_can_be_deleted_and_the_pages_that_used_it_keep_their_colours(): void
    {
        $theme = $this->theme('Brand dusk');
        $landing = $this->landingWithTheme('Keeps colours');
        $before = $landing->fresh()->content['theme'];

        $this->post(route('landing.themes.destroy', $theme->id))->assertRedirect(route('landing.themes.index'));

        $this->assertFalse(LandingTheme::whereKey($theme->id)->exists());
        $this->assertSame($before, $landing->fresh()->content['theme']);
    }

    public function test_the_editor_offers_the_custom_themes_beside_the_built_in_ones(): void
    {
        $theme = $this->theme('Brand dusk');
        $landing = $this->landingWithTheme('Editor page');

        $this->get(route('landing.manage.edit', $landing->id))
            ->assertOk()
            ->assertSee('data-preset="custom-'.$theme->id.'"', false)
            ->assertSee('Brand dusk')
            ->assertSee('data-preset="builtin-gold"', false);
    }

    public function test_a_user_who_can_only_view_cannot_change_themes(): void
    {
        $this->actingAs($this->staff(['landing-list']), 'admin');

        $this->get(route('landing.themes.index'))->assertOk();
        $this->post(route('landing.themes.store'), [
            'name' => 'Blocked', 'primary' => '#111111', 'accent' => '#222222', 'background' => '#333333', 'text' => '#444444',
        ])->assertForbidden();

        $this->assertFalse(LandingTheme::where('name', 'Blocked')->exists());
    }
}
