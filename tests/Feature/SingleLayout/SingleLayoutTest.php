<?php

namespace Tests\Feature\SingleLayout;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\SingleLayout as Layout;
use App\Models\Theme;
use App\Models\User;
use App\Services\Landing\LandingThemes;
use App\Services\SingleLayout\SingleLayout;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Single Layout: the saved product detail page designs. The active one is applied to every product page, the editor
 * saves named layouts, and the preview listener is only ever added for an admin.
 */
class SingleLayoutTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        // An installed site has a settings row; the storefront footer reads it. The boot-time share ran before this row
        // existed, so it is shared again here.
        $settings = GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);
        view()->share('generalsetting', $settings);
        $this->actingAs(User::first(), 'admin');
        Cache::flush();

        $category = Category::create(['name' => 'Layouts', 'slug' => 'layouts-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Layout item', 'slug' => 'layout-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'SL-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
        app(StockManagementService::class)->stockIn($this->product, [
            'quantity' => 5, 'unit_cost' => 100, 'selling_price' => 200, 'is_active_for_website' => true,
        ]);
    }

    /** A layout with the given overrides on top of the defaults. */
    private function layout(string $name, array $overrides = [], bool $active = false): Layout
    {
        $settings = SingleLayout::defaults();
        foreach ($overrides as $path => $value) {
            data_set($settings, $path, $value);
        }

        return Layout::create(['name' => $name, 'settings' => $settings, 'is_active' => $active]);
    }

    /** The form the editor posts: every colour, shape and switch, as the browser sends them. */
    private function form(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Editable',
            'colours' => [
                'primary' => '#6658dd', 'accent' => '#e82165', 'badge_hover' => '#b91c52', 'background' => '#ffffff', 'text' => '#1f2937',
                'order_now' => '#6658dd', 'order_now_hover' => '#5246b1', 'order_now_text' => '#ffffff', 'order_now_hover_text' => '#ffffff',
                'add_to_cart' => '#6658dd', 'add_to_cart_hover' => '#5246b1', 'add_to_cart_text' => '#ffffff', 'add_to_cart_hover_text' => '#ffffff',
                'phone' => '#198754', 'phone_hover' => '#146c43', 'phone_text' => '#ffffff', 'phone_hover_text' => '#ffffff',
                'whatsapp' => '#198754', 'whatsapp_hover' => '#146c43', 'whatsapp_text' => '#ffffff', 'whatsapp_hover_text' => '#ffffff',
            ],
            'shapes' => ['button' => 'rounded', 'badge' => 'circle'],
            'sections' => ['description' => '1', 'write_review' => '1', 'related' => '1'],
            'layout' => ['gallery' => 'left'],
            'extra_text' => '',
            'custom_buttons' => [],
        ], $overrides);
    }

    private function productPage(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('product', $this->product->slug).($query ? '?'.http_build_query($query) : ''));
    }

    private function staff(array $permissions): User
    {
        $user = User::create([
            'name' => 'Staff '.uniqid(), 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $user->givePermissionTo(Permission::where('guard_name', 'admin')->whereIn('name', $permissions)->get());

        return $user;
    }

    public function test_the_active_layout_is_applied_to_the_product_page(): void
    {
        $this->layout('Right side', [
            'layout.gallery' => 'right',
            'layout.sticky_info' => true,
            'sections.reviews' => false,
            'colours.add_to_cart' => '#123456',
            'shapes.button' => 'square',
            'shapes.badge' => 'circle',
        ], true);

        // The class list is checked as a whole attribute: the page's own CSS also names these classes.
        $this->productPage()
            ->assertOk()
            ->assertSee('class="sl-scope sl-gallery-right sl-sticky-info sl-button-square sl-badge-circle sl-hide-reviews"', false)
            ->assertSee('--sl-add-to-cart: #123456', false);
    }

    public function test_without_an_active_layout_the_page_keeps_the_site_look(): void
    {
        $this->productPage()
            ->assertOk()
            ->assertSee('class="sl-scope sl-button-rounded sl-badge-circle"', false)
            ->assertSee('--sl-accent: #e82165', false)
            ->assertSee('<div class="sl-extra-text"></div>', false)
            ->assertSee('<div class="sl-custom-buttons" data-sl-custom-list></div>', false)
            ->assertDontSee('--sl-add-to-cart: #123456', false);
    }

    public function test_a_switched_off_section_is_hidden_on_the_product_page_by_its_class(): void
    {
        $this->layout('No related', ['sections.related' => false], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('class="sl-scope sl-button-rounded sl-badge-circle sl-hide-related"', false);
    }

    public function test_the_order_now_call_whatsapp_and_shipping_buttons_can_be_switched_off(): void
    {
        $this->layout('Fewer buttons', [
            'sections.order_now' => false,
            'sections.call' => false,
            'sections.whatsapp' => false,
            'sections.shipping' => false,
        ], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('class="sl-scope sl-button-rounded sl-badge-circle sl-hide-order_now sl-hide-call sl-hide-whatsapp sl-hide-shipping"', false)
            ->assertSee('data-sl-section="order_now"', false)
            ->assertSee('data-sl-section="call"', false)
            ->assertSee('data-sl-section="whatsapp"', false)
            ->assertSee('data-sl-section="shipping"', false);
    }

    public function test_the_call_and_the_whatsapp_buttons_have_separate_switches(): void
    {
        $this->layout('Only WhatsApp', ['sections.call' => false], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('class="sl-scope sl-button-rounded sl-badge-circle sl-hide-call"', false);
    }

    public function test_the_hover_and_discount_badge_colours_reach_the_page(): void
    {
        $this->layout('Colours', [
            'colours.order_now_hover' => '#112233',
            'colours.badge_hover' => '#445566',
            'colours.accent' => '#778899',
        ], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('--sl-order-now-hover: #112233', false)
            ->assertSee('--sl-badge-hover: #445566', false)
            ->assertSee('--sl-accent: #778899', false);
    }

    public function test_each_button_has_four_colours_that_reach_the_page(): void
    {
        $this->layout('Four colours', [
            'colours.order_now' => '#0a0b0c',
            'colours.order_now_hover' => '#0a0b0d',
            'colours.order_now_text' => '#eeeeee',
            'colours.order_now_hover_text' => '#dddddd',
        ], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('--sl-order-now: #0a0b0c', false)
            ->assertSee('--sl-order-now-hover: #0a0b0d', false)
            ->assertSee('--sl-order-now-text: #eeeeee', false)
            ->assertSee('--sl-order-now-hover-text: #dddddd', false)
            ->assertSee('--sl-btn-color: var(--sl-order-now-text)', false)
            ->assertSee('--sl-btn-hover-color: var(--sl-order-now-hover-text)', false);
    }

    public function test_each_default_button_type_has_its_own_colour_on_the_page(): void
    {
        $this->layout('Buttons', [
            'colours.add_to_cart' => '#0a0b0c',
            'colours.phone' => '#010203',
            'colours.whatsapp' => '#040506',
        ], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('--sl-add-to-cart: #0a0b0c', false)
            ->assertSee('--sl-phone: #010203', false)
            ->assertSee('--sl-whatsapp: #040506', false)
            ->assertSee('class="btn btn-success w-100 call_now_btn sl-btn-phone"', false)
            ->assertSee('class="btn btn-success w-100 call_now_btn sl-btn-whatsapp"', false);
    }

    public function test_custom_buttons_show_under_the_buy_box_with_their_four_colours(): void
    {
        $this->layout('Custom', [
            'custom_buttons' => [[
                'label' => 'Call us', 'link' => 'tel:+8801519607646',
                'background' => '#112233', 'hover_background' => '#223344', 'text' => '#ffffff', 'hover_text' => '#eeeeee',
            ]],
        ], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('href="tel:+8801519607646"', false)
            ->assertSee('style="--sl-custom-bg: #112233; --sl-custom-hover-bg: #223344; --sl-custom-text: #ffffff; --sl-custom-hover-text: #eeeeee"', false)
            ->assertSee('>Call us</a>', false);
    }

    public function test_a_layout_or_cache_from_before_a_switch_existed_still_renders_with_its_default(): void
    {
        // A layout saved when there was one combined contact switch, and a cache filled from that older shape.
        Layout::create(['name' => 'Old shape', 'is_active' => true, 'settings' => ['sections' => ['contact' => false]]]);
        Cache::put('single_layout.active', ['sections' => ['contact' => false]], 600);

        $this->productPage()
            ->assertOk()
            ->assertSee('class="sl-scope sl-button-rounded sl-badge-circle"', false);
    }

    public function test_the_extra_text_is_shown_under_the_buy_box_when_it_is_set(): void
    {
        $this->layout('Extra', ['extra_text' => 'Free delivery inside Dhaka'], true);

        $this->productPage()
            ->assertOk()
            ->assertSee('<div class="sl-extra-text">Free delivery inside Dhaka</div>', false);
    }

    public function test_the_preview_listener_is_only_added_for_an_admin(): void
    {
        $this->productPage(['sl_preview' => 1])->assertOk()->assertSee("e.data.source !== 'sl-parent'", false);

        auth()->guard('admin')->logout();
        $this->productPage(['sl_preview' => 1])->assertOk()->assertDontSee("e.data.source !== 'sl-parent'", false);
    }

    public function test_only_one_layout_is_active_at_a_time(): void
    {
        $first = $this->layout('First', [], true);
        $second = $this->layout('Second');

        $this->post(route('single-layout.activate', $second->id))->assertRedirect(route('single-layout.index'));

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_the_active_layout_cannot_be_deleted(): void
    {
        $active = $this->layout('Active', [], true);

        $this->post(route('single-layout.destroy', $active->id))->assertRedirect();

        $this->assertTrue(Layout::whereKey($active->id)->exists());
    }

    public function test_a_duplicate_is_not_active_until_it_is_activated(): void
    {
        $source = $this->layout('Source', ['layout.gallery' => 'right'], true);

        $this->post(route('single-layout.duplicate', $source->id))->assertRedirect();

        $copy = Layout::where('name', 'Source (copy)')->firstOrFail();
        $this->assertFalse($copy->is_active);
        $this->assertSame('right', $copy->settings['layout']['gallery']);
    }

    public function test_saving_a_layout_stores_its_colours_sections_and_gallery_side(): void
    {
        $layout = $this->layout('Editable');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'colours' => ['primary' => '#AA0000', 'add_to_cart' => '#0000AA', 'add_to_cart_hover' => '#0000CC', 'add_to_cart_text' => '#FFFFFF'],
            'layout' => ['gallery' => 'right', 'sticky_info' => '1'],
        ]))->assertRedirect(route('single-layout.edit', $layout->id));

        $settings = $layout->fresh()->settings;
        $this->assertSame('#aa0000', $settings['colours']['primary'], 'colours are stored in lower case');
        $this->assertSame('#0000aa', $settings['colours']['add_to_cart']);
        $this->assertSame('#0000cc', $settings['colours']['add_to_cart_hover']);
        $this->assertSame('#ffffff', $settings['colours']['add_to_cart_text']);
        $this->assertFalse($settings['sections']['reviews'], 'an unticked section is switched off');
        $this->assertFalse($settings['sections']['order_now'], 'an unticked on/off button is switched off');
        $this->assertFalse($settings['sections']['call']);
        $this->assertFalse($settings['sections']['whatsapp']);
        $this->assertFalse($settings['sections']['shipping']);
        $this->assertTrue($settings['sections']['related']);
        $this->assertSame('right', $settings['layout']['gallery']);
        $this->assertTrue($settings['layout']['sticky_info']);
    }

    public function test_the_button_and_badge_shapes_are_saved(): void
    {
        $layout = $this->layout('Shapes');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'shapes' => ['button' => 'square', 'badge' => 'rounded'],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['button' => 'square', 'badge' => 'rounded'], $layout->fresh()->settings['shapes']);
    }

    public function test_an_unknown_shape_is_refused(): void
    {
        $layout = $this->layout('Shapes refused');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'shapes' => ['button' => 'hexagon'],
        ]))->assertSessionHasErrors('shapes.button');
    }

    public function test_the_extra_text_is_saved_up_to_200_characters_and_refused_beyond(): void
    {
        $layout = $this->layout('Extra text');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'extra_text' => '  Free delivery inside Dhaka  ',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('Free delivery inside Dhaka', $layout->fresh()->settings['extra_text']);

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'extra_text' => str_repeat('a', 201),
        ]))->assertSessionHasErrors('extra_text');
    }

    public function test_a_custom_button_is_saved_with_its_four_colours_and_a_blank_row_is_left_out(): void
    {
        $layout = $this->layout('Custom saved');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'custom_buttons' => [
                [
                    'label' => '  Call us  ', 'link' => 'tel:+8801519607646',
                    'background' => '#112233', 'hover_background' => '#223344', 'text' => '#FFFFFF', 'hover_text' => '#EEEEEE',
                ],
                ['label' => '', 'link' => '', 'background' => '#000000', 'hover_background' => '#000000', 'text' => '#000000', 'hover_text' => '#000000'],
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(
            [[
                'label' => 'Call us', 'link' => 'tel:+8801519607646',
                'background' => '#112233', 'hover_background' => '#223344', 'text' => '#ffffff', 'hover_text' => '#eeeeee',
            ]],
            $layout->fresh()->settings['custom_buttons']
        );
    }

    public function test_a_custom_button_link_must_open_a_web_address_a_phone_or_an_email(): void
    {
        $layout = $this->layout('Unsafe link');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'custom_buttons' => [['label' => 'Bad', 'link' => 'javascript:alert(1)', 'background' => '#112233', 'hover_background' => '#223344']],
        ]))->assertSessionHasErrors('custom_buttons.0.link');
    }

    public function test_a_custom_button_with_a_text_needs_a_link(): void
    {
        $layout = $this->layout('No link');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'custom_buttons' => [['label' => 'Bad', 'link' => '', 'background' => '#112233', 'hover_background' => '#223344']],
        ]))->assertSessionHasErrors('custom_buttons.0.link');
    }

    public function test_no_more_than_three_custom_buttons_can_be_saved(): void
    {
        $layout = $this->layout('Too many');
        $button = ['label' => 'Button', 'link' => 'https://example.com', 'background' => '#112233', 'hover_background' => '#223344'];

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'custom_buttons' => [$button, $button, $button, $button],
        ]))->assertSessionHasErrors('custom_buttons');
    }

    public function test_a_colour_that_is_not_hex_is_refused(): void
    {
        $layout = $this->layout('Refuse');

        $this->post(route('single-layout.update', $layout->id), $this->form([
            'colours' => ['primary' => 'red'],
        ]))->assertSessionHasErrors('colours.primary');
    }

    public function test_the_hover_colour_is_a_little_darker_than_the_button_by_default(): void
    {
        $this->assertSame('#d9d9d9', SingleLayout::darken('#ffffff'));
    }

    public function test_the_text_colour_on_a_button_is_black_or_white_whichever_reads_better(): void
    {
        $this->assertSame('#ffffff', SingleLayout::contrast('#198754'), 'the default green takes white text');
        $this->assertSame('#111111', SingleLayout::contrast('#f5f5f5'), 'a light button takes dark text');
    }

    public function test_the_default_phone_and_whatsapp_buttons_keep_the_green_the_page_has_always_used(): void
    {
        $defaults = SingleLayout::defaults()['colours'];

        $this->assertSame('#198754', $defaults['phone']);
        $this->assertSame('#198754', $defaults['whatsapp']);
        $this->assertSame('#ffffff', $defaults['phone_text'], 'the default button text is white on green');
        $this->assertSame($defaults['primary'], $defaults['order_now'], 'Order Now starts from the primary colour');
    }

    public function test_the_theme_presets_are_the_landing_palettes_with_a_full_colour_set_each(): void
    {
        $presets = SingleLayout::themePresets();

        $this->assertSame(array_merge(['site'], array_keys(LandingThemes::presets())), array_keys($presets));
        foreach ($presets as $key => $preset) {
            if ($key !== 'site') {
                $this->assertSame(LandingThemes::presets()[$key]['label'], $preset['label']);
            }
            foreach (SingleLayout::COLOUR_KEYS as $colour) {
                $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $preset['colours'][$colour], "preset {$key} colour {$colour}");
            }
            $this->assertSame($preset['colours']['primary'], $preset['colours']['order_now'], "preset {$key}: Order Now is the primary");
        }
    }

    public function test_the_first_theme_item_is_the_active_site_theme(): void
    {
        Theme::query()->update(['is_default' => false]);
        Theme::create([
            'name' => 'Festival', 'slug' => 'festival-'.uniqid(), 'is_default' => true,
            'font_family' => 'Arial', 'heading_font' => 'Arial',
            'primary_color' => '#112233', 'accent_color' => '#445566', 'body_bg_color' => '#fafafa', 'text_color' => '#101010',
            'button_bg_color' => '#334455', 'sale_badge_bg' => '#ff0055',
        ]);

        $presets = SingleLayout::themePresets();
        $this->assertSame('site', array_key_first($presets));

        $site = $presets['site'];
        $this->assertSame('Site theme: Festival', $site['label']);
        $this->assertSame('#112233', $site['colours']['primary']);
        $this->assertSame('#112233', $site['colours']['order_now'], 'Order Now is the theme primary');
        $this->assertSame('#334455', $site['colours']['add_to_cart'], 'Add to Cart is the theme button colour');
        $this->assertSame('#ff0055', $site['colours']['accent'], 'the discount badge is the theme sale badge colour');
        $this->assertSame('#fafafa', $site['colours']['background']);
        $this->assertSame('#101010', $site['colours']['text']);

        $this->get(route('single-layout.edit', $this->layout('Site theme')->id))
            ->assertOk()
            ->assertSee('Site theme: Festival');
    }

    public function test_the_site_theme_is_the_one_the_storefront_uses_not_just_any_active_theme(): void
    {
        // Several themes can carry is_active; the storefront uses the one in the general settings, else the default.
        Theme::query()->update(['is_default' => false]);
        Theme::create([
            'name' => 'Default one', 'slug' => 'default-one-'.uniqid(), 'is_active' => true, 'is_default' => true,
            'font_family' => 'Arial', 'heading_font' => 'Arial', 'primary_color' => '#111111',
        ]);
        $chosen = Theme::create([
            'name' => 'Chosen one', 'slug' => 'chosen-one-'.uniqid(), 'is_active' => true,
            'font_family' => 'Arial', 'heading_font' => 'Arial', 'primary_color' => '#222222',
        ]);

        $this->assertSame('Site theme: Default one', SingleLayout::siteThemePreset()['label']);
        $this->assertSame('#111111', SingleLayout::defaults()['colours']['primary']);

        GeneralSetting::query()->update(['theme_id' => $chosen->id]);

        $this->assertSame('Site theme: Chosen one', SingleLayout::siteThemePreset()['label']);
        $this->assertSame('#222222', SingleLayout::defaults()['colours']['primary']);
    }

    public function test_the_editor_offers_the_theme_presets_the_four_colours_of_each_button_and_custom_buttons(): void
    {
        $layout = $this->layout('Editor');

        $response = $this->get(route('single-layout.edit', $layout->id))
            ->assertOk()
            ->assertSee('sl-theme-preset', false)
            ->assertSee('data-colours=', false)
            ->assertSee(LandingThemes::presets()['gold']['label'])
            ->assertSee('Hover background')
            ->assertSee('Hover text')
            ->assertSee('Add custom button')
            ->assertSee('id="sl-custom-template"', false);

        foreach (SingleLayout::BUTTON_KEYS as $key) {
            foreach (array_keys(SingleLayout::BUTTON_PARTS) as $suffix) {
                $response->assertSee('name="colours['.$key.$suffix.']"', false);
            }
        }
    }

    public function test_the_editor_shows_the_saved_custom_buttons(): void
    {
        $layout = $this->layout('Editor custom', [
            'custom_buttons' => [[
                'label' => 'Call us', 'link' => 'tel:+8801519607646',
                'background' => '#112233', 'hover_background' => '#223344', 'text' => '#ffffff', 'hover_text' => '#eeeeee',
            ]],
        ]);

        $this->get(route('single-layout.edit', $layout->id))
            ->assertOk()
            ->assertSee('name="custom_buttons[0][label]" value="Call us"', false)
            ->assertSee('name="custom_buttons[0][link]" value="tel:+8801519607646"', false)
            ->assertSee('name="custom_buttons[0][hover_text]" value="#eeeeee"', false);
    }

    public function test_the_editor_offers_the_on_off_buttons_shapes_and_extra_text(): void
    {
        $layout = $this->layout('Editor');

        $this->get(route('single-layout.edit', $layout->id))
            ->assertOk()
            ->assertSee('Order Now button')
            ->assertSee('Call button')
            ->assertSee('WhatsApp button')
            ->assertSee('Shipping info')
            ->assertSee('name="shapes[button]"', false)
            ->assertSee('name="shapes[badge]"', false)
            ->assertSee('name="extra_text"', false);
    }

    public function test_the_call_and_whatsapp_buttons_use_the_icons_of_the_icon_font_the_site_loads(): void
    {
        // The site loads Font Awesome 6: the WhatsApp logo is a brand icon, and the phone is a solid icon.
        $this->productPage()
            ->assertOk()
            ->assertSee('<i class="fa-solid fa-square-phone"></i>', false)
            ->assertSee('<i class="fa-brands fa-whatsapp"></i>', false);
    }

    public function test_the_menu_links_to_single_layout_under_the_theme_system(): void
    {
        $this->get(route('single-layout.index'))
            ->assertOk()
            ->assertSee('Single Layout')
            ->assertSee(route('single-layout.index'), false);
    }

    public function test_a_user_who_can_only_view_cannot_save_a_layout(): void
    {
        $layout = $this->layout('Locked');
        $this->actingAs($this->staff(['theme-list']), 'admin');

        $this->get(route('single-layout.edit', $layout->id))->assertOk();
        $this->post(route('single-layout.update', $layout->id), $this->form(['name' => 'Hacked']))->assertForbidden();

        $this->assertSame('Locked', $layout->fresh()->name);
    }
}
