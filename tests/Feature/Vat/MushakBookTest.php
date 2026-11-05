<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\Vat\MushakBook;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Mushak 6.1 and 6.2 books in the official column layout, and the DEMO data that fills them.
 */
class MushakBookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
    }

    private function book(): MushakBook
    {
        return new MushakBook(app(VatReportService::class));
    }

    public function test_demo_data_fills_both_books_with_sample_lines(): void
    {
        $this->artisan('vat:demo', ['--month' => '2026-10'])->assertSuccessful();

        $from = Carbon::parse('2026-10-01');
        $to = Carbon::parse('2026-10-31');
        $this->assertCount(2, app(VatReportService::class)->purchaseLines($from, $to));
        $this->assertCount(4, app(VatReportService::class)->salesLines($from, $to));
    }

    public function test_running_the_demo_again_creates_nothing_new(): void
    {
        $this->artisan('vat:demo', ['--month' => '2026-10'])->assertSuccessful();
        $this->artisan('vat:demo', ['--month' => '2026-10'])->assertSuccessful();

        $this->assertCount(4, app(VatReportService::class)->salesLines(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')));
    }

    public function test_demo_data_is_refused_while_vat_is_off(): void
    {
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $this->artisan('vat:demo', ['--month' => '2026-10'])->assertFailed();
    }

    public function test_the_sales_book_has_the_official_columns_and_the_buyer_details(): void
    {
        $this->artisan('vat:demo', ['--month' => '2026-10'])->assertSuccessful();

        $book = $this->book()->salesBook(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame('মূসক-৬.২', $book['form']);
        $this->assertCount(21, $book['columns']);
        $this->assertCount(4, $book['rows']);
        $this->assertSame('DEMO Buyer Two (registered)', collect($book['rows'])->firstWhere('invoice', 'DEMO-6.2-'.now()->setDate(2026, 10, 1)->format('Ym').'-02')['buyer_name']);
        $this->assertEqualsWithDelta(array_sum(array_column($book['rows'], 'taxable')), $book['totals']['taxable'], 0.001);
    }

    public function test_the_purchase_book_has_the_official_columns_and_the_supplier_bin(): void
    {
        $this->artisan('vat:demo', ['--month' => '2026-10'])->assertSuccessful();

        $book = $this->book()->purchaseBook(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame('মূসক-৬.১', $book['form']);
        $this->assertCount(21, $book['columns']);
        $this->assertSame('123456789012', $book['rows'][0]['supplier_bin']);
        $this->assertTrue($book['draft'], 'the book stays a draft until the mapping is approved');
    }
}
