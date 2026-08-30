<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\HomepageLayout;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The storefront homepage must render a successful response.
     *
     * The test DB is empty, so we seed the default/base data (general settings,
     * contacts, themes, …) first — otherwise the homepage 500s because the
     * layout reads `$generalsetting->name` etc.
     */
    public function test_homepage_returns_a_successful_response()
    {
        $this->seed(DefaultDatabaseSeeder::class);

        // Keep the homepage lightweight for the test: no product-heavy sections.
        $setting = GeneralSetting::first();
        $setting->update([
            'show_all_products'           => 0,
            'show_category_wise_products' => 0,
            'active_layout_id'            => null,
        ]);
        HomepageLayout::query()->update(['is_active' => false]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee($setting->name, false);
    }
}
