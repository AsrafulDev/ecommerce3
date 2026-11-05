<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The opening balance lines are typed in, so their fields are full size, not the small table style. */
class OpeningPageSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_opening_lines_use_full_size_fields(): void
    {
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        // The opening balances are an Advanced Accounting screen, so the module is switched on for the test.
        config(['double-entry.enabled' => true]);

        $this->get(route('admin.accounting.opening.index'))
            ->assertOk()
            ->assertSee('id="openingLines"', false)
            ->assertDontSee('table-sm align-middle mb-0" id="openingLines"', false)
            ->assertSee('#openingLines input.form-control', false);
    }
}
