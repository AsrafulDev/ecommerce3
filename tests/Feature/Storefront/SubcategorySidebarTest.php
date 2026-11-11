<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Subcategory;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The filter sidebar on the subcategory page: each block (the child categories under the subcategory's name, the price
 * range, and the child category filter) is drawn only when it has something to show.
 */
class SubcategorySidebarTest extends TestCase
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

    private function subcategory(): Subcategory
    {
        $category = Category::create(['name' => 'Womens', 'slug' => 'womens-'.uniqid(), 'status' => 1, 'parent_id' => 0]);

        return Subcategory::create([
            'subcategoryName' => 'Three-Piece Set', 'slug' => 'three-piece-set-'.uniqid(), 'category_id' => $category->id, 'status' => 1,
        ]);
    }

    private function product(Subcategory $subcategory, float $price = 1500): Product
    {
        return Product::create([
            'name' => 'Set '.uniqid(), 'slug' => 'set-'.uniqid(), 'category_id' => $subcategory->category_id,
            'product_code' => 'SC-'.uniqid(), 'purchase_price' => 900, 'new_price' => $price, 'stock' => 0, 'status' => 1,
            'approval_status' => 'approved',
        ]);
    }

    public function test_every_sidebar_block_is_hidden_when_the_subcategory_has_nothing_in_it(): void
    {
        $subcategory = $this->subcategory();

        $this->get(route('subcategory', $subcategory->slug))
            ->assertOk()
            ->assertDontSee('id="category_sidebar"', false)
            ->assertDontSee('id="price_sidebar"', false)
            ->assertDontSee('id="filter_sidebar"', false);
    }

    public function test_the_child_category_blocks_are_shown_when_there_are_child_categories(): void
    {
        $subcategory = $this->subcategory();
        Childcategory::create([
            'childcategoryName' => 'Cotton', 'slug' => 'cotton-'.uniqid(), 'subcategory_id' => $subcategory->id, 'status' => 1,
        ]);

        $this->get(route('subcategory', $subcategory->slug))
            ->assertOk()
            ->assertSee('id="category_sidebar"', false)
            ->assertSee('id="filter_sidebar"', false)
            ->assertSee('Cotton');
    }

    public function test_the_price_block_is_shown_when_products_have_a_price(): void
    {
        $subcategory = $this->subcategory();
        $this->product($subcategory);

        $this->get(route('subcategory', $subcategory->slug))
            ->assertOk()
            ->assertSee('id="price_sidebar"', false);
    }
}
