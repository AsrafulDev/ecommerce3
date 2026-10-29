<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A filter section with nothing to choose from is not shown on the shop and category pages.
 */
class StorefrontFilterVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Plain pricing: the price range comes from the product, not from stock batches.
        config(['pricing.batch_wise' => false]);
        // An installed site has a settings row; without one the storefront redirects to /install.
        // The app shares the settings row at boot, before this test creates it, so share it again here.
        // Columns NOT NULL without a default in strict MySQL.
        $settings = \App\Models\GeneralSetting::forceCreate([
            'name' => 'Test Shop',
            'white_logo' => 'public/logo.png',
            'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png',
            'status' => 1,
        ]);
        // The layout caches the settings row, so an earlier empty result must not be reused.
        \Illuminate\Support\Facades\Cache::forget('general_setting');
        \Illuminate\Support\Facades\Cache::forget('active_theme');
        view()->share('generalsetting', $settings);
    }

    public function test_price_filter_is_hidden_when_the_page_has_no_products(): void
    {
        $this->get(route('shop'))
            ->assertOk()
            ->assertDontSee('id="price_sidebar"', false);
    }

    public function test_price_filter_is_shown_when_the_page_has_products(): void
    {
        $category = Category::create(['name' => 'Filter Test', 'slug' => 'filter-test', 'status' => 1, 'parent_id' => 0]);
        Product::create([
            'name' => 'Filter Item', 'slug' => 'filter-item', 'category_id' => $category->id,
            'product_code' => 'FI-1', 'purchase_price' => 50, 'new_price' => 100, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);

        $this->get(route('shop'))
            ->assertOk()
            ->assertSee('id="price_sidebar"', false);
    }

    public function test_category_page_hides_the_price_filter_when_it_has_no_products(): void
    {
        $category = Category::create(['name' => 'Empty Cat', 'slug' => 'empty-cat', 'status' => 1, 'parent_id' => 0]);

        $this->get(route('category', $category->slug))
            ->assertOk()
            ->assertDontSee('id="price_sidebar"', false);
    }
}
