<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;

/**
 * The sidebar's Reports menu lists the core reports always, and adds the Books
 * reports only while Advanced Accounting is enabled.
 */
class ReportsMenuTest extends AccountingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    public function test_reports_menu_adds_books_reports_when_advanced_accounting_is_on(): void
    {
        config(['double-entry.enabled' => true]);

        $this->get(route('admin.reports.challans'))
            ->assertOk()
            ->assertSee('sidebar-reports')
            ->assertSee('Challan register')
            ->assertSee('Trial Balance')
            ->assertSee('Income Statement')
            ->assertSee('Cash &amp; Bank Ledger', false);
    }

    public function test_reports_menu_shows_only_core_reports_when_advanced_accounting_is_off(): void
    {
        config(['double-entry.enabled' => false]);

        $this->get(route('admin.reports.challans'))
            ->assertOk()
            ->assertSee('sidebar-reports')
            ->assertSee('Challan register')
            ->assertSee('Sales &amp; order report', false)
            ->assertDontSee('Trial Balance')
            ->assertDontSee('Income Statement');
    }

    public function test_report_links_are_no_longer_under_transactions_or_accounting(): void
    {
        config(['double-entry.enabled' => true]);

        $this->get(route('admin.reports.challans'))
            ->assertOk()
            ->assertDontSee('Operational reports (Core)')
            ->assertDontSee('Trial Balance (Books)');
    }
}
