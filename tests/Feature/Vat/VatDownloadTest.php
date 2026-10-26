<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
    }

    private function sale(string $invoice, string $at): void
    {
        $product = Product::create([
            'name' => 'Dl '.$invoice, 'slug' => 'dl-'.uniqid(), 'category_id' => 1, 'product_code' => 'DL-'.uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1, 'vat_rate' => 15,
        ]);
        $order = Order::create([
            'invoice_id' => $invoice, 'amount' => 200, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => $at,
        ]);
        OrderDetails::create(['order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 1, 'product_name' => 'Dl', 'purchase_price' => 100]);
    }

    private function purchase(string $invoice, string $date): void
    {
        $product = Product::create([
            'name' => 'Dp '.$invoice, 'slug' => 'dp-'.uniqid(), 'category_id' => 1, 'product_code' => 'DP-'.uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1, 'vat_rate' => 15,
        ]);
        $purchase = Purchase::create([
            'supplier_id' => null, 'invoice_no' => $invoice, 'purchase_date' => $date,
            'amount' => 1000, 'grand_total' => 1000, 'paid_amount' => 0, 'due_amount' => 1000, 'created_by' => 1,
        ]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'qty' => 1, 'unit_cost' => 1000, 'line_total' => 1000]);
    }

    private function csvRows(string $url): array
    {
        $response = $this->get($url)->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));
        array_shift($lines);

        return $lines;
    }

    public function test_one_invoice_downloads_only_that_invoice(): void
    {
        $this->sale('INV-A', '2026-10-05 10:00:00');
        $this->sale('INV-B', '2026-10-05 11:00:00');

        $rows = $this->csvRows(route('admin.vat.export', ['type' => 'sales', 'mode' => 'invoice', 'invoice' => 'INV-A']));

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('INV-A', $rows[0]);
    }

    public function test_one_day_downloads_only_that_day(): void
    {
        $this->sale('INV-D1', '2026-10-05 10:00:00');
        $this->sale('INV-D2', '2026-10-06 10:00:00');

        $rows = $this->csvRows(route('admin.vat.export', ['type' => 'sales', 'mode' => 'date', 'date' => '2026-10-05']));

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('INV-D1', $rows[0]);
    }

    public function test_a_date_range_includes_both_ends(): void
    {
        $this->sale('INV-R1', '2026-10-01 09:00:00');
        $this->sale('INV-R2', '2026-10-15 09:00:00');
        $this->sale('INV-R3', '2026-10-16 09:00:00');

        $rows = $this->csvRows(route('admin.vat.export', ['type' => 'sales', 'mode' => 'range', 'from' => '2026-10-01', 'to' => '2026-10-15']));

        $this->assertCount(2, $rows);
    }

    public function test_purchase_by_invoice_and_by_day(): void
    {
        $this->purchase('PUR-1', '2026-10-03');
        $this->purchase('PUR-2', '2026-10-04');

        $byInvoice = $this->csvRows(route('admin.vat.export', ['type' => 'purchase', 'mode' => 'invoice', 'invoice' => 'PUR-2']));
        $byDay = $this->csvRows(route('admin.vat.export', ['type' => 'purchase', 'mode' => 'date', 'date' => '2026-10-03']));

        $this->assertCount(1, $byInvoice);
        $this->assertStringContainsString('PUR-2', $byInvoice[0]);
        $this->assertCount(1, $byDay);
        $this->assertStringContainsString('PUR-1', $byDay[0]);
    }

    public function test_the_file_name_says_what_it_contains(): void
    {
        $this->sale('INV-N', '2026-10-05 10:00:00');

        $response = $this->get(route('admin.vat.export', ['type' => 'sales', 'mode' => 'date', 'date' => '2026-10-05']));

        $this->assertStringContainsString('vat-sales-date-2026-10-05.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_an_unknown_mode_or_missing_value_is_refused(): void
    {
        $this->get(route('admin.vat.export', ['type' => 'sales', 'mode' => 'everything']))->assertSessionHasErrors('mode');
        $this->get(route('admin.vat.export', ['type' => 'sales', 'mode' => 'invoice']))->assertSessionHasErrors('invoice');
    }

    public function test_the_reports_page_offers_the_three_choices(): void
    {
        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee('One invoice')
            ->assertSee('One day')
            ->assertSee('Date range');
    }
}
