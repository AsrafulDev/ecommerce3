<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Subcategory;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The filter sidebar on the category and shop pages: a block is drawn only when it has something to list, so an empty
 * category or brand block never appears.
 */
class CategorySidebarTest extends TestCase
{
    use RefreshDatabase;

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
        Cache::flush();
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Mobile Phone', 'slug' => 'mobile-phone-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
    }

    private function product(Category $category, ?Brand $brand = null): Product
    {
        return Product::create([
            'name' => 'Handset '.uniqid(), 'slug' => 'handset-'.uniqid(), 'category_id' => $category->id, 'brand_id' => $brand?->id,
            'product_code' => 'CS-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
    }

    public function test_the_category_block_is_hidden_when_the_category_has_no_subcategories(): void
    {
        $category = $this->category();
        $this->product($category);

        $this->get(route('category', $category->slug))
            ->assertOk()
            ->assertDontSee('id="category_sidebar"', false)
            ->assertDontSee('id="collapseCat"', false);
    }

    public function test_the_category_block_lists_the_subcategories_when_there_are_some(): void
    {
        $category = $this->category();
        $this->product($category);
        Subcategory::create([
            'subcategoryName' => 'Smartphone', 'slug' => 'smartphone-'.uniqid(), 'category_id' => $category->id, 'status' => 1,
        ]);

        $this->get(route('category', $category->slug))
            ->assertOk()
            ->assertSee('id="category_sidebar"', false)
            ->assertSee('id="collapseCat"', false)
            ->assertSee('Smartphone');
    }

    public function test_the_brand_block_is_hidden_when_no_product_in_the_category_has_a_brand(): void
    {
        $category = $this->category();
        $this->product($category);

        $this->get(route('category', $category->slug))
            ->assertOk()
            ->assertDontSee('id="collapseBrand"', false);
    }

    public function test_the_brand_block_lists_the_brands_of_the_category(): void
    {
        $category = $this->category();
        $brand = Brand::create(['name' => 'Samsung', 'slug' => 'samsung-'.uniqid(), 'status' => 1]);
        $this->product($category, $brand);

        $this->get(route('category', $category->slug))
            ->assertOk()
            ->assertSee('id="collapseBrand"', false)
            ->assertSee('Samsung');
    }

    public function test_the_shop_page_hides_the_brand_block_when_no_product_has_a_brand(): void
    {
        $this->product($this->category());

        $this->get(route('shop'))
            ->assertOk()
            ->assertDontSee('id="collapseBrand"', false);
    }
}
