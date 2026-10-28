<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The VAT reports page states whether the return is final, and lists what is still open.
 * It is never final while a decision, the practitioner's sign-off, or the BIN is missing.
 */
class VatReportStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    public function test_page_has_no_working_draft_title_and_lists_open_items(): void
    {
        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertDontSee('VAT Reports (working draft)')
            ->assertSee('Not final.')
            ->assertSee('The filing frequency is not set')
            ->assertSee('The category-to-line mapping has not been approved');
    }

    public function test_frequency_saved_in_settings_closes_its_open_item(): void
    {
        $this->post(route('admin.vat.update'), [
            'vat_filing_frequency' => 'quarterly',
        ])->assertSessionHasNoErrors();

        $this->assertSame('quarterly', GeneralSetting::orderBy('id', 'desc')->first()->vat_filing_frequency);

        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertDontSee('The filing frequency is not set');
    }

    public function test_an_unknown_frequency_is_refused(): void
    {
        $this->post(route('admin.vat.update'), ['vat_filing_frequency' => 'yearly'])
            ->assertSessionHasErrors('vat_filing_frequency');
    }

    public function test_return_becomes_final_once_every_open_item_is_closed(): void
    {
        config(['vat.mapping_approved' => true]);
        $this->post(route('admin.vat.update'), [
            'vat_enabled' => 1,
            'vat_prices_include_vat' => 1,
            'vat_bin' => '123456789012',
            'vat_filing_frequency' => 'monthly',
        ])->assertSessionHasNoErrors();

        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee('Final: no open items for this period.')
            ->assertDontSee('Not final.');
    }
}
