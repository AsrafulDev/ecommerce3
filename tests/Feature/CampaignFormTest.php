<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The create and edit landing-page forms are one form: the same sections, labels and
 * section switches, so a page built on create can be edited the same way.
 */
class CampaignFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    public function test_create_page_has_the_same_sections_as_edit(): void
    {
        $this->get(route('campaign.create'))
            ->assertOk()
            ->assertSee('Create Campaign')
            ->assertSee('labels[hero_eyebrow]', false)
            ->assertSee('sections[hero][visible]', false)
            ->assertSee('name="review"', false)
            ->assertSee('name="status"', false);
    }

    public function test_edit_page_still_opens_for_a_saved_campaign(): void
    {
        $campaign = $this->campaign();

        $this->get(route('campaign.edit', $campaign->id))
            ->assertOk()
            ->assertSee('Update Campaign')
            ->assertSee('name="hidden_id"', false);
    }

    public function test_a_campaign_created_from_the_form_saves_labels_sections_and_status(): void
    {
        $product = $this->product();

        $this->post(route('campaign.store'), [
            'name' => 'Form Campaign',
            'review' => '4.9 (120)',
            'description' => 'Full text',
            'short_description' => 'Short text',
            'image_one_url' => 'uploads/media/one.webp',
            'product_id' => [$product->id],
            'status' => 1,
            'labels' => ['hero_eyebrow' => 'Limited offer'],
            'sections' => ['hero' => ['visible' => 1]],
        ])->assertSessionHasNoErrors();

        $campaign = Campaign::where('name', 'Form Campaign')->firstOrFail();
        $this->assertSame(1, (int) $campaign->status);
        $this->assertSame('Limited offer', $campaign->labels['hero_eyebrow']);
        $this->assertTrue($campaign->sectionConfig()['hero']['visible']);
    }

    public function test_a_campaign_saved_without_the_status_switch_is_inactive(): void
    {
        $product = $this->product();

        $this->post(route('campaign.store'), [
            'name' => 'Draft Campaign',
            'review' => '4.5',
            'description' => 'Full text',
            'short_description' => 'Short text',
            'image_one_url' => 'uploads/media/one.webp',
            'product_id' => [$product->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, (int) Campaign::where('name', 'Draft Campaign')->value('status'));
    }

    private function product(): Product
    {
        $category = \App\Models\Category::create(['name' => 'Campaign Test', 'slug' => 'campaign-test', 'status' => 1, 'parent_id' => 0]);

        return Product::create([
            'name' => 'Campaign Item', 'slug' => 'campaign-item', 'category_id' => $category->id,
            'product_code' => 'CI-1', 'purchase_price' => 50, 'new_price' => 100, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
    }

    private function campaign(): Campaign
    {
        $product = $this->product();

        return Campaign::create([
            'name' => 'Saved Campaign',
            'slug' => 'saved-campaign',
            'product_id' => $product->id,
            'review' => '4.8',
            'description' => 'Full text',
            'short_description' => 'Short text',
            'image_one' => 'uploads/media/one.webp',
            'status' => 1,
        ]);
    }
}
