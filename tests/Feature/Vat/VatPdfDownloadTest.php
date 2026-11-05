<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        GeneralSetting::query()->update([
            'vat_enabled' => true, 'vat_prices_include_vat' => false,
            'vat_bin' => '123456789012', 'vat_business_name' => 'Test Trader',
        ]);
    }

    private function sale(string $invoice): void
    {
        $product = Product::create([
            'name' => 'Pdf '.$invoice, 'slug' => 'pdf-'.uniqid(), 'category_id' => 1, 'product_code' => 'PD-'.uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1, 'vat_rate' => 15,
        ]);
        $order = Order::create([
            'invoice_id' => $invoice, 'amount' => 200, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => '2026-10-05 10:00:00',
        ]);
        OrderDetails::create(['order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 1, 'product_name' => 'Pdf', 'purchase_price' => 100]);
    }

    public function test_a_pdf_download_is_a_real_pdf_named_for_its_selection(): void
    {
        $this->sale('INV-PDF-1');

        $response = $this->get(route('admin.vat.export', ['type' => 'sales', 'mode' => 'date', 'date' => '2026-10-05', 'format' => 'pdf']))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('mushak-6.2-date-2026-10-05.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_pdf_is_the_official_book_layout_marked_as_a_draft_until_approved(): void
    {
        $html = view('backEnd.vat.mushak-book', [
            'book' => (new \App\Services\Vat\MushakBook(app(\App\Services\Vat\VatReportService::class)))
                ->salesBook(\Carbon\Carbon::parse('2026-10-05'), \Carbon\Carbon::parse('2026-10-05')),
            'business' => ['name' => 'Test Trader', 'address' => 'Dhaka', 'bin' => '123456789012'],
            'generated' => 'now',
        ])->render();

        $this->assertStringContainsString('মূসক-৬.২', $html);
        $this->assertStringContainsString('বিক্রয় হিসাব পুস্তক', $html);
        $this->assertStringContainsString('NOT APPROVED FOR FILING', $html);
        $this->assertStringContainsString('Test Trader', $html);
    }

    public function test_an_unknown_format_is_refused(): void
    {
        $this->get(route('admin.vat.export', ['type' => 'sales', 'mode' => 'range', 'format' => 'xlsx']))
            ->assertSessionHasErrors('format');
    }
}
