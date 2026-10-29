<?php

namespace Tests\Feature\Landing;

use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Landing\TemplateRegistry;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The admin list, the create popup, the editor and its save and settings endpoints.
 * Ownership: staff with landing permissions manage only their own pages; landing-admin sees all.
 */
class ManageLandingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);

        $category = \App\Models\Category::create(['name' => 'Manage', 'slug' => 'manage', 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Managed Item', 'slug' => 'managed-item', 'category_id' => $category->id, 'product_code' => 'MG-1',
            'purchase_price' => 1, 'new_price' => 500, 'stock' => 0, 'status' => 1, 'approval_status' => 'approved',
        ]);

        $this->actingAs(User::first(), 'admin');
    }

    /** A staff user with landing permissions but not the Admin role. */
    private function staff(array $permissions): User
    {
        $user = User::create([
            'name' => 'Staff ' . uniqid(), 'email' => uniqid() . '@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $user->givePermissionTo(Permission::where('guard_name', 'admin')->whereIn('name', $permissions)->get());

        return $user;
    }

    private function landing(array $overrides = []): LandingTemplate
    {
        return LandingTemplate::create(array_merge([
            'name' => 'Managed Landing', 'slug' => 'managed-landing-' . uniqid(),
            'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id,
            'content' => \App\Services\Landing\ContentValidator::clean(
                TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY
            ),
            'status' => false,
        ], $overrides));
    }

    public function test_the_list_opens_for_an_admin(): void
    {
        $this->landing(['name' => 'Listed Landing']);

        $this->get(route('landing.manage.index'))
            ->assertOk()
            ->assertSee('Listed Landing')
            ->assertSee('New landing page');
    }

    public function test_a_blank_title_is_refused(): void
    {
        $this->post(route('landing.manage.store'), [
            'name' => '', 'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id,
        ])->assertSessionHasErrors('name');

        $this->assertSame(0, LandingTemplate::count());
    }

    public function test_an_unknown_template_is_refused(): void
    {
        $this->post(route('landing.manage.store'), [
            'name' => 'Bad', 'template_key' => 'nope', 'product_id' => $this->product->id,
        ])->assertSessionHasErrors('template_key');
    }

    public function test_create_saves_a_draft_with_the_default_content_and_opens_the_editor(): void
    {
        $response = $this->post(route('landing.manage.store'), [
            'name' => 'Summer Launch', 'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id,
        ]);

        $landing = LandingTemplate::where('name', 'Summer Launch')->firstOrFail();
        $response->assertRedirect(route('landing.manage.edit', $landing->id));
        $this->assertFalse($landing->status);
        $this->assertSame('summer-launch', $landing->slug);
        $this->assertSame('Limited time offer', $landing->content['elements']['hero.eyebrow']['value']);
        $this->assertSame(User::first()->id, (int) $landing->created_by);
    }

    public function test_the_editor_opens_with_the_frame_and_the_settings(): void
    {
        $landing = $this->landing();

        $this->get(route('landing.manage.edit', $landing->id))
            ->assertOk()
            ->assertSee('id="lpFrame"', false)
            ->assertSee('lpSettings', false)
            ->assertSee('Publishing');
    }

    public function test_the_frame_is_editable_and_the_preview_is_not(): void
    {
        $landing = $this->landing();

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('contenteditable="true"', false)
            ->assertSee('data-el="hero.title"', false)
            ->assertSee('lp-row-add', false);

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 0]))
            ->assertOk()
            ->assertDontSee('contenteditable', false)
            ->assertDontSee('data-el=', false);
    }

    public function test_saving_an_element_stores_it_and_the_page_shows_it(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.save', $landing->id), [
            'elements' => ['hero.title' => ['value' => 'New headline']],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('New headline', $landing->fresh()->content['elements']['hero.title']['value']);
        $this->get('/landing/' . $landing->fresh()->slug . '')->assertStatus(404); // still a draft
    }

    public function test_an_unknown_element_is_refused_with_a_message(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.save', $landing->id), [
            'elements' => ['hero.secret' => ['value' => 'x']],
        ])->assertStatus(422)->assertJsonPath('errors.0', "Unknown element 'hero.secret'.");
    }

    public function test_switching_off_the_checkout_is_refused(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.save', $landing->id), [
            'sections' => ['checkout' => ['enabled' => false]],
        ])->assertStatus(422);
    }

    public function test_switching_every_content_section_off_is_refused(): void
    {
        $landing = $this->landing();
        $sections = [];
        foreach (array_keys(TemplateRegistry::schema(TemplateRegistry::LUXURY)['sections']) as $key) {
            $sections[$key] = ['enabled' => false];
        }
        $sections['checkout'] = ['enabled' => true];

        $this->postJson(route('landing.manage.save', $landing->id), ['sections' => $sections])->assertStatus(422);
    }

    public function test_a_colour_is_saved_and_must_be_hex(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.save', $landing->id), ['theme' => ['primary' => '#123456']])
            ->assertOk();
        $this->assertSame('#123456', $landing->fresh()->content['theme']['primary']);

        $this->postJson(route('landing.manage.save', $landing->id), ['theme' => ['primary' => 'red']])
            ->assertStatus(422);
    }

    public function test_settings_save_status_validity_and_discount(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.settings', $landing->id), [
            'status' => true,
            'valid_from' => now()->subDay()->toDateTimeString(),
            'valid_until' => now()->addWeek()->toDateTimeString(),
            'extra_discount_type' => 'percent',
            'extra_discount_value' => 10,
        ])->assertOk();

        $fresh = $landing->fresh();
        $this->assertTrue($fresh->status);
        $this->assertSame('percent', $fresh->extra_discount_type);
        $this->assertEquals(10, (float) $fresh->extra_discount_value);
        $this->assertNotNull($fresh->valid_until);
    }

    public function test_a_percent_discount_above_one_hundred_is_refused(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.settings', $landing->id), [
            'extra_discount_type' => 'percent', 'extra_discount_value' => 150,
        ])->assertStatus(422);
    }

    public function test_a_coupon_is_created_once_and_then_updated(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.settings', $landing->id), [
            'coupon_type' => 'percent', 'coupon_value' => 15,
        ])->assertOk();
        $coupon = $landing->fresh()->coupon;
        $this->assertNotNull($coupon);
        $this->assertStringStartsWith('LP', $coupon->code);

        $this->postJson(route('landing.manage.settings', $landing->id), [
            'coupon_type' => 'fixed', 'coupon_value' => 50,
        ])->assertOk();
        $this->assertSame(1, \App\Models\Coupon::where('id', $coupon->id)->count());
        $this->assertSame('fixed', $coupon->fresh()->type);
    }

    public function test_a_staff_member_creates_and_sees_only_their_own_pages(): void
    {
        $alice = $this->staff(['landing-list', 'landing-create', 'landing-edit']);
        $bob = $this->staff(['landing-list', 'landing-create', 'landing-edit']);

        $this->actingAs($alice, 'admin');
        $this->post(route('landing.manage.store'), [
            'name' => 'Alice Page', 'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id,
        ])->assertRedirect();
        $alicePage = LandingTemplate::where('name', 'Alice Page')->firstOrFail();
        $this->assertSame($alice->id, (int) $alicePage->created_by);

        $this->actingAs($bob, 'admin');
        $this->get(route('landing.manage.index'))
            ->assertOk()
            ->assertDontSee('Alice Page');
        $this->get(route('landing.manage.edit', $alicePage->id))->assertForbidden();
        $this->postJson(route('landing.manage.save', $alicePage->id), ['theme' => ['primary' => '#000000']])->assertForbidden();
    }

    public function test_a_landing_admin_sees_and_edits_every_page(): void
    {
        $owner = $this->staff(['landing-list', 'landing-create', 'landing-edit']);
        $boss = $this->staff(['landing-list', 'landing-edit', 'landing-admin']);
        $page = $this->landing(['name' => 'Owned Page', 'created_by' => $owner->id]);

        $this->actingAs($boss, 'admin');
        $this->get(route('landing.manage.index'))->assertOk()->assertSee('Owned Page');
        $this->get(route('landing.manage.edit', $page->id))->assertOk();
    }

    public function test_a_user_without_create_permission_cannot_create(): void
    {
        $viewer = $this->staff(['landing-list']);
        $this->actingAs($viewer, 'admin');

        $this->post(route('landing.manage.store'), [
            'name' => 'Nope', 'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id,
        ])->assertForbidden();
    }

    public function test_checkout_labels_and_placeholders_are_editable_in_the_frame(): void
    {
        $landing = $this->landing();

        $this->get(route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]))
            ->assertOk()
            ->assertSee('data-el="checkout.label_name"', false)
            ->assertSee('data-el="checkout.placeholder_name"', false)
            ->assertSee('data-ph-for="lp-name"', false);
    }

    public function test_a_checkout_label_or_placeholder_is_saved(): void
    {
        $landing = $this->landing();

        $this->postJson(route('landing.manage.save', $landing->id), [
            'elements' => [
                'checkout.label_name' => ['value' => 'Your name'],
                'checkout.placeholder_address' => ['value' => 'Flat, road, area'],
            ],
        ])->assertOk();

        $stored = $landing->fresh()->content['elements'];
        $this->assertSame('Your name', $stored['checkout.label_name']['value']);
        $this->assertSame('Flat, road, area', $stored['checkout.placeholder_address']['value']);
    }

    public function test_discount_and_coupon_are_planned_and_hidden_in_the_editor(): void
    {
        $landing = $this->landing();

        $this->get(route('landing.manage.edit', $landing->id))
            ->assertOk()
            ->assertSee('data-planned="discount-coupon"', false)
            ->assertSee('class="d-none" data-planned', false);
    }

    public function test_saving_settings_without_discount_fields_keeps_the_stored_discount(): void
    {
        $landing = $this->landing(['extra_discount_type' => 'percent', 'extra_discount_value' => 12]);

        $this->postJson(route('landing.manage.settings', $landing->id), ['status' => true])->assertOk();

        $fresh = $landing->fresh();
        $this->assertTrue($fresh->status);
        $this->assertSame('percent', $fresh->extra_discount_type);
        $this->assertEquals(12, (float) $fresh->extra_discount_value);
    }

    public function test_the_editor_saves_only_when_asked(): void
    {
        $landing = $this->landing();

        $this->get(route('landing.manage.edit', $landing->id))
            ->assertOk()
            ->assertSee('id="lpSave"', false)
            ->assertDontSee('setTimeout(flush', false);
    }

    public function test_an_old_page_with_removed_fields_can_still_be_saved(): void
    {
        $landing = $this->landing(['template_key' => TemplateRegistry::FASHION, 'content' => array_replace_recursive(
            TemplateRegistry::defaultContent(TemplateRegistry::FASHION),
            ['elements' => [
                'care.item.1.title' => ['value' => 'Old title'],
                'sizes.item.1.size' => ['value' => 'S'],
            ], 'sections' => ['sizes' => ['enabled' => true]]]
        )]);

        $this->postJson(route('landing.manage.save', $landing->id), [
            'elements' => ['hero.title' => ['value' => 'Updated headline']],
        ])->assertOk()->assertJson(['ok' => true]);

        $stored = $landing->fresh()->content;
        $this->assertSame('Updated headline', $stored['elements']['hero.title']['value']);
        $this->assertArrayNotHasKey('sizes.item.1.size', $stored['elements']);
        $this->assertArrayNotHasKey('care.item.1.title', $stored['elements']);
        $this->assertArrayNotHasKey('sizes', $stored['sections']);
    }

    public function test_the_list_offers_delete_with_a_warning_popup(): void
    {
        $landing = $this->landing(['name' => 'Delete Me']);

        $this->get(route('landing.manage.index'))
            ->assertOk()
            ->assertSee('id="landingDelete"', false)
            ->assertSee('Delete this landing page?')
            ->assertSee(route('landing.manage.destroy', $landing->id), false);
    }

    public function test_delete_removes_the_page_and_switches_off_its_coupon(): void
    {
        $landing = $this->landing();
        $coupon = \App\Services\Landing\LandingPricing::issueCoupon($landing, 'percent', 10);

        $this->post(route('landing.manage.destroy', $landing->id))
            ->assertRedirect(route('landing.manage.index'));

        $this->assertNull(LandingTemplate::find($landing->id));
        $this->assertSame(0, (int) \App\Models\Coupon::find($coupon->id)->status);
    }

    public function test_a_staff_member_cannot_delete_someone_elses_page(): void
    {
        $owner = $this->staff(['landing-list', 'landing-create', 'landing-edit']);
        $other = $this->staff(['landing-list', 'landing-edit']);
        $page = $this->landing(['created_by' => $owner->id]);

        $this->actingAs($other, 'admin');
        $this->post(route('landing.manage.destroy', $page->id))->assertForbidden();

        $this->assertNotNull(LandingTemplate::find($page->id));
    }
}
