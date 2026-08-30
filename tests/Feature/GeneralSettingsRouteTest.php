<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * General settings are a SINGLE record on this single-vendor site, so the
 * edit URL must NOT contain an id: `/admin/settings/edit` (name `settings.edit`).
 */
class GeneralSettingsRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
    }

    public function test_edit_route_has_no_id_segment(): void
    {
        $url = route('settings.edit');
        $this->assertSame('/admin/settings/edit', parse_url($url, PHP_URL_PATH));
    }

    public function test_index_redirects_to_edit(): void
    {
        $this->get('/admin/settings/manage')
            ->assertRedirect(route('settings.edit'));
    }

    public function test_edit_page_renders(): void
    {
        $this->get('/admin/settings/edit')
            ->assertStatus(200)
            ->assertSee(GeneralSetting::first()->name, false);
    }

    public function test_update_redirects_back_to_edit_without_id(): void
    {
        $setting = GeneralSetting::first();

        $this->post('/admin/settings/update', [
            'id'   => $setting->id,
            'name' => 'Updated Shop Name',
        ])->assertRedirect(route('settings.edit'));

        $this->assertEquals('Updated Shop Name', GeneralSetting::first()->name);
    }
}
