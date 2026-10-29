<?php

namespace Tests\Feature\Landing;

use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The older landing pages are only listed now: they can be switched on or off and deleted,
 * and the create and edit pages are gone.
 */
class CampaignListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    public function test_the_list_opens_without_create_or_edit_links(): void
    {
        $this->get(route('campaign.index'))
            ->assertOk()
            ->assertDontSee('Create New Page')
            ->assertDontSee('/campaign/create', false);
    }

    public function test_the_create_and_edit_pages_are_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('campaign.create'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('campaign.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('campaign'));
    }
}
