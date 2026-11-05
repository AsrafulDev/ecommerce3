<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Services\Vat\Mushak91Draft;
use App\Services\Vat\VatCreditNoteService;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The credit-note registers, the exceptions list, the totals and the reconciliation on the VAT reports page.
 */
class VatReportRegistersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
    }

    /** A product with a VAT rate, unless the rate is null: a line of such a product gets no VAT snapshot at all. */
    private function product(string $name, ?string $category, ?float $rate = 15.0): Product
    {
        return Product::create([
            'name' => $name, 'slug' => 'vr-'.uniqid(), 'category_id' => 1, 'product_code' => 'VR-'.uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1, 'vat_rate' => $rate,
            'vat_category' => $category,
        ]);
    }

    /** A completed sale of 2 x 200 with its VAT snapshot: taxable 400, VAT 60. A null VAT means the line has none. */
    private function sale(string $invoice, Product $product, ?float $vat = 60.0, ?Carbon $at = null): Order
    {
        $order = Order::create([
            'invoice_id' => $invoice, 'amount' => 460, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => ($at ?? now())->toDateTimeString(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => $product->name, 'purchase_price' => 100,
            'taxable_value' => $vat === null ? null : 400, 'vat_amount' => $vat,
        ]);

        return $order->fresh();
    }

    /** A purchase of one line worth 1000 plus 150 VAT. */
    private function purchase(string $invoice, Product $product): PurchaseItem
    {
        $purchase = Purchase::create([
            'supplier_id' => null, 'invoice_no' => $invoice, 'purchase_date' => now()->toDateString(),
            'amount' => 1150, 'grand_total' => 1150, 'paid_amount' => 0, 'due_amount' => 1150, 'created_by' => 1,
        ]);

        return PurchaseItem::create([
            'purchase_id' => $purchase->id, 'product_id' => $product->id, 'qty' => 1, 'unit_cost' => 1000,
            'line_total' => 1000, 'taxable_value' => 1000, 'vat_amount' => 150,
        ]);
    }

    private function thisMonth(): array
    {
        return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    }

    /** @return list<string> the CSV rows after the header */
    private function csvRows(string $url): array
    {
        $response = $this->get($url)->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));
        array_shift($lines);

        return $lines;
    }

    public function test_a_sale_credit_note_is_listed_with_its_invoice_and_its_vat(): void
    {
        $order = $this->sale('INV-CN-1', $this->product('Shirt', 'standard'));
        $note = app(VatCreditNoteService::class)->forOrder($order, 'Returned in the shop');
        [$from, $to] = $this->thisMonth();

        $response = $this->get(route('admin.vat.export', ['type' => 'credit-sales', 'mode' => 'range', 'from' => $from, 'to' => $to]))->assertOk();
        $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));

        $this->assertSame(
            ['Credit Note No', 'Date', 'Invoice No', 'Buyer', 'Taxable Value', 'Output VAT Reversed', 'Reason'],
            str_getcsv($lines[0])
        );
        $this->assertCount(2, $lines);
        $this->assertStringContainsString($note->credit_note_no, $lines[1]);
        $this->assertStringContainsString('INV-CN-1', $lines[1]);
        $this->assertStringContainsString('Returned in the shop', $lines[1]);
        $this->assertSame(['400', '60'], array_slice(str_getcsv($lines[1]), 4, 2));
    }

    public function test_a_credit_note_list_can_be_filtered_to_one_invoice(): void
    {
        $service = app(VatCreditNoteService::class);
        $service->forOrder($this->sale('INV-CN-A', $this->product('A', 'standard')));
        $service->forOrder($this->sale('INV-CN-B', $this->product('B', 'standard')));

        $rows = $this->csvRows(route('admin.vat.export', ['type' => 'credit-sales', 'mode' => 'invoice', 'invoice' => 'INV-CN-B']));

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('INV-CN-B', $rows[0]);
    }

    public function test_a_purchase_credit_note_is_listed_with_its_purchase_and_its_input_vat(): void
    {
        $item = $this->purchase('PUR-CN-1', $this->product('Stock', 'standard'));
        $note = app(VatCreditNoteService::class)->forPurchaseReturn($item, 1);
        [$from, $to] = $this->thisMonth();

        $response = $this->get(route('admin.vat.export', ['type' => 'credit-purchases', 'mode' => 'range', 'from' => $from, 'to' => $to]))->assertOk();
        $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));

        $this->assertSame('Input VAT Reversed', str_getcsv($lines[0])[5]);
        $this->assertStringContainsString($note->credit_note_no, $lines[1]);
        $this->assertStringContainsString('PUR-CN-1', $lines[1]);
    }

    public function test_a_credit_note_list_is_csv_only(): void
    {
        $this->get(route('admin.vat.export', ['type' => 'credit-sales', 'mode' => 'range', 'format' => 'pdf']))
            ->assertStatus(422);
    }

    public function test_the_page_shows_totals_reconciliation_exceptions_and_credit_notes(): void
    {
        $service = app(VatCreditNoteService::class);
        $standard = $this->product('Tailored Shirt', 'standard');
        $service->forOrder($this->sale('INV-PG-1', $standard));
        $this->sale('INV-PG-2', $standard, 60.0);
        $this->sale('INV-PG-3', $this->product('Missing VAT Item', 'standard', null), null);
        $this->sale('INV-PG-4', $this->product('Plain Item', null), 30.0);

        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee('Return summary (Mushak 9.1)')
            ->assertSee('Reconciliation')
            ->assertSee('All checks agree')
            ->assertSee('Credit notes on sales')
            ->assertSee('VAT by product category')
            ->assertSee('Products with no VAT category')
            ->assertSee('Plain Item')
            ->assertSee('Sales lines with no VAT recorded')
            ->assertSee('Missing VAT Item')
            ->assertSee('Lines to fix')
            ->assertSee('3 invoice(s)')
            ->assertSee('Last quarter');
    }

    public function test_the_reconciliation_agrees_on_a_clean_period_and_lists_every_check(): void
    {
        $standard = $this->product('Clean Item', 'standard');
        app(VatCreditNoteService::class)->forOrder($this->sale('INV-REC-1', $standard));
        $item = $this->purchase('PUR-REC-1', $standard);
        app(VatCreditNoteService::class)->forPurchaseReturn($item, 1);

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $reports = app(VatReportService::class);
        $draft = (new Mushak91Draft())->build($from, $to, $reports);
        $checks = $reports->reconciliation($from, $to, $draft);

        $this->assertCount(6, $checks);
        foreach ($checks as $check) {
            $this->assertTrue($check['agrees'], $check['label'].' should agree');
            $this->assertSame(0.0, $check['difference']);
        }
    }

    public function test_the_exceptions_tab_is_empty_when_every_line_has_vat_and_a_category(): void
    {
        $this->sale('INV-OK-1', $this->product('Fine Item', 'standard'));

        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee('Nothing to fix in this period.');
    }
}
