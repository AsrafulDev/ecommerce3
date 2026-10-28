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

class VatReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    private function vatSale(): void
    {
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
        $product = Product::create([
            'name' => 'Report Item', 'slug' => 'rep-' . uniqid(), 'category_id' => 1, 'product_code' => 'RP-' . uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1, 'vat_rate' => 15,
        ]);
        $order = Order::create([
            'invoice_id' => 'INV-REP-1', 'amount' => 460, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => now()->toDateTimeString(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Report Item', 'purchase_price' => 100,
        ]);
    }

    public function test_report_page_opens_and_warns_while_vat_is_off(): void
    {
        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee('Return summary (Mushak 9.1)')
            ->assertSee('VAT is switched off');
    }

    public function test_sales_csv_has_the_working_columns_and_the_sale(): void
    {
        $this->vatSale();

        $response = $this->get(route('admin.vat.export', ['type' => 'sales']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $lines = explode("\n", trim($response->getContent()));
        $this->assertSame(['Invoice No', 'Invoice Date', 'Buyer BIN', 'Taxable Value', 'Output VAT'], str_getcsv($lines[0]));
        $this->assertStringContainsString('INV-REP-1', $lines[1]);
        $this->assertStringContainsString('400', $lines[1]);
        $this->assertStringContainsString('60', $lines[1]);
    }

    public function test_purchase_csv_has_the_supplier_columns(): void
    {
        $response = $this->get(route('admin.vat.export', ['type' => 'purchase']));

        $response->assertOk();
        $this->assertStringContainsString('Supplier BIN', $response->getContent());
    }

    public function test_unknown_register_is_not_found(): void
    {
        $this->get('/admin/vat/reports/export/unknown')->assertNotFound();
    }
}
