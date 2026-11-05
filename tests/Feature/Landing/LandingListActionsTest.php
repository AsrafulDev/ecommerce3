<?php

namespace Tests\Feature\Landing;

use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\TemplateRegistry;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The landing list actions: duplicate, change product, and title and link. None of them changes the template.
 */
class LandingListActionsTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);

        $category = \App\Models\Category::create(['name' => 'List actions', 'slug' => 'list-actions', 'status' => 1, 'parent_id' => 0]);
        $this->product = $this->makeProduct('Listed item', true);

        $this->actingAs(User::first(), 'admin');
    }

    private function makeProduct(string $name, bool $active): Product
    {
        $category = \App\Models\Category::first() ?? \App\Models\Category::create(['name' => 'Cat', 'slug' => 'cat-'.uniqid(), 'status' => 1, 'parent_id' => 0]);

        return Product::create([
            'name' => $name, 'slug' => 'p-'.uniqid(), 'category_id' => $category->id, 'product_code' => 'LA-'.uniqid(),
            'purchase_price' => 1, 'new_price' => 500, 'stock' => 0, 'status' => $active ? 1 : 0, 'approval_status' => 'approved',
        ]);
    }

    /** A staff user with the given landing permissions, and not the Admin role. */
    private function staff(array $permissions): User
    {
        $user = User::create([
            'name' => 'Staff '.uniqid(), 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $user->givePermissionTo(Permission::where('guard_name', 'admin')->whereIn('name', $permissions)->get());

        return $user;
    }

    private function landing(array $overrides = []): LandingTemplate
    {
        return LandingTemplate::create(array_merge([
            'name' => 'Spring Sale', 'slug' => 'spring-sale-'.uniqid(),
            'template_key' => TemplateRegistry::LUXURY, 'product_id' => $this->product->id,
            'content' => ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY),
            'status' => true,
        ], $overrides));
    }

    public function test_the_list_offers_title_and_link_change_product_and_duplicate(): void
    {
        $this->landing(['name' => 'Listed landing']);

        $this->get(route('landing.manage.index'))
            ->assertOk()
            ->assertSee('Title & link')
            ->assertSee('Change product')
            ->assertSee('Duplicate')
            ->assertSee('id="landingProduct"', false)
            ->assertSee('id="landingDetails"', false);
    }

    public function test_duplicate_copies_the_template_content_and_product_as_a_draft_with_its_own_link(): void
    {
        $source = $this->landing(['name' => 'Spring Sale', 'status' => true, 'valid_from' => now()->subDay(), 'valid_until' => now()->addWeek()]);

        $this->post(route('landing.manage.duplicate', $source->id))->assertRedirect();

        $copy = LandingTemplate::where('name', 'Spring Sale (copy)')->firstOrFail();
        $this->assertNotSame($source->id, $copy->id);
        $this->assertSame($source->template_key, $copy->template_key);
        $this->assertSame($source->product_id, $copy->product_id);
        $this->assertSame($source->content, $copy->content);
        $this->assertNotSame($source->slug, $copy->slug, 'the copy gets its own link name');
        $this->assertFalse((bool) $copy->status, 'the copy is a draft');
        $this->assertNull($copy->coupon_id, 'the coupon is not copied');
        $this->assertNull($copy->valid_from);
        $this->assertNull($copy->valid_until);
        $this->assertSame(auth('admin')->id(), (int) $copy->created_by);

        $this->assertTrue((bool) $source->fresh()->status, 'the source is left as it was');
    }

    public function test_a_user_without_landing_create_cannot_duplicate(): void
    {
        $source = $this->landing();
        $this->actingAs($this->staff(['landing-list', 'landing-edit']), 'admin');

        $this->post(route('landing.manage.duplicate', $source->id))->assertForbidden();
    }

    public function test_change_product_points_the_landing_at_another_product_and_keeps_everything_else(): void
    {
        $landing = $this->landing(['name' => 'Keep me']);
        $other = $this->makeProduct('Other item', true);
        $before = [$landing->template_key, $landing->slug, $landing->content, $landing->name];

        $this->post(route('landing.manage.product', $landing->id), ['product_id' => $other->id])
            ->assertRedirect(route('landing.manage.index'));

        $fresh = $landing->fresh();
        $this->assertSame($other->id, $fresh->product_id);
        $this->assertSame($before, [$fresh->template_key, $fresh->slug, $fresh->content, $fresh->name]);
    }

    public function test_an_inactive_product_is_refused_for_a_change(): void
    {
        $landing = $this->landing();
        $inactive = $this->makeProduct('Retired item', false);

        $this->post(route('landing.manage.product', $landing->id), ['product_id' => $inactive->id])
            ->assertSessionHasErrorsIn('product', 'product_id');

        $this->assertSame($this->product->id, $landing->fresh()->product_id);
    }

    public function test_title_and_link_change_takes_the_new_values_and_never_the_template(): void
    {
        $landing = $this->landing(['name' => 'Old title', 'slug' => 'old-link']);

        $this->post(route('landing.manage.details', $landing->id), [
            'name' => 'New title',
            'slug' => 'Fresh Link!',
            'template_key' => TemplateRegistry::GROCERY,
        ])->assertRedirect(route('landing.manage.index'));

        $fresh = $landing->fresh();
        $this->assertSame('New title', $fresh->name);
        $this->assertSame('fresh-link', $fresh->slug, 'the link name is cleaned up');
        $this->assertSame(TemplateRegistry::LUXURY, $fresh->template_key, 'the template is never changed here');
    }

    public function test_a_link_name_another_page_uses_is_refused(): void
    {
        $this->landing(['name' => 'Taken', 'slug' => 'taken-link']);
        $landing = $this->landing(['name' => 'Mine', 'slug' => 'my-link']);

        $this->post(route('landing.manage.details', $landing->id), ['name' => 'Mine', 'slug' => 'taken-link'])
            ->assertSessionHasErrorsIn('details', 'slug');

        $this->assertSame('my-link', $landing->fresh()->slug);
    }

    public function test_keeping_the_same_link_name_is_allowed(): void
    {
        $landing = $this->landing(['name' => 'Mine', 'slug' => 'kept-link']);

        $this->post(route('landing.manage.details', $landing->id), ['name' => 'Mine again', 'slug' => 'kept-link'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Mine again', $landing->fresh()->name);
    }

    public function test_the_old_link_stops_working_after_the_link_name_changes(): void
    {
        $landing = $this->landing(['slug' => 'before-change']);

        $this->post(route('landing.manage.details', $landing->id), ['name' => $landing->name, 'slug' => 'after-change']);

        $this->get('/landing/before-change')->assertNotFound();
    }

    public function test_a_staff_user_cannot_change_the_title_and_link_of_someone_elses_page(): void
    {
        $landing = $this->landing();
        $this->actingAs($this->staff(['landing-list', 'landing-edit']), 'admin');

        $this->post(route('landing.manage.details', $landing->id), ['name' => 'Hijacked', 'slug' => 'hijacked'])
            ->assertForbidden();

        $this->assertSame('Spring Sale', $landing->fresh()->name);
    }
}
