<?php

namespace Tests\Feature\HeaderFooter;

use App\Models\GeneralSetting;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The header & footer builder's own stylesheet and script are versioned by their modified time. The release does not carry
 * public/ files, so the page must still open when they are not on the server.
 */
class HeaderFooterAssetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        // An installed site has a settings row; the admin layout reads it. The boot-time share ran before this row existed.
        $settings = GeneralSetting::forceCreate([
            'name' => 'Test Shop', 'white_logo' => 'public/logo.png', 'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png', 'status' => 1,
        ]);
        view()->share('generalsetting', $settings);
        $this->actingAs(User::first(), 'admin');
        Cache::flush();
    }

    public function test_the_builder_still_opens_when_its_assets_are_not_on_the_server(): void
    {
        $this->app->usePublicPath(sys_get_temp_dir().'/no-public-assets-'.uniqid());

        $this->get(route('headerfooter.index'))
            ->assertOk()
            ->assertSee('headerfooter-builder.css?v=0', false)
            ->assertSee('headerfooter-builder.js?v=0', false);
    }

    public function test_the_builder_versions_its_assets_by_their_modified_time_when_they_exist(): void
    {
        $this->get(route('headerfooter.index'))
            ->assertOk()
            ->assertSee('headerfooter-builder.css?v='.filemtime(public_path('backEnd/css/headerfooter-builder.css')), false)
            ->assertSee('headerfooter-builder.js?v='.filemtime(public_path('backEnd/js/headerfooter-builder.js')), false);
    }
}
