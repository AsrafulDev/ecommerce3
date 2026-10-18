<?php

namespace Tests\Feature\Vat;

use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatMenuLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_menu_links_to_the_vat_pages(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');

        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee(route('admin.vat.edit'))
            ->assertSee(route('admin.vat.reports'));
    }
}
