<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\VatCreditNote;
use App\Services\Vat\Mushak91Draft;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Mushak91DraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
    }

    private function product(string $category, ?string $rate = null): Product
    {
        return Product::create([
            'name' => 'Draft ' . $category, 'slug' => 'd-' . uniqid(), 'category_id' => 1,
            'product_code' => 'D-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200,
            'stock' => 0, 'status' => 1, 'vat_rate' => $rate ?? 15, 'vat_category' => $category ?: null,
        ]);
    }

    private function sale(Product $product, float $price, int $qty): void
    {
        $order = Order::create([
            'invoice_id' => 'INV-D-' . uniqid(), 'amount' => $price * $qty, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => '2026-10-07 10:00:00',
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => $price, 'qty' => $qty,
            'product_name' => $product->name, 'purchase_price' => 100,
        ]);
    }

    private function purchase(Product $product, float $total): void
    {
        $purchase = Purchase::create([
            'supplier_id' => null, 'invoice_no' => 'PD-' . uniqid(), 'purchase_date' => '2026-10-05',
            'amount' => $total, 'grand_total' => $total, 'paid_amount' => 0, 'due_amount' => $total, 'created_by' => 1,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id, 'product_id' => $product->id, 'qty' => 1,
            'unit_cost' => $total, 'line_total' => $total,
        ]);
    }

    private function draft(): array
    {
        return (new Mushak91Draft())->build(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), new VatReportService());
    }

    public function test_standard_sale_and_purchase_land_on_their_lines_and_net_is_line_nine_minus_twenty_three(): void
    {
        $this->sale($this->product('standard'), 200, 2);          // taxable 400, VAT 60
        $this->purchase($this->product('standard'), 1000);        // VAT 150

        $draft = $this->draft();

        $this->assertTrue($draft['draft']);
        $this->assertEqualsWithDelta(400.0, $draft['lines'][4]['value'], 0.001, 'standard sales on line 4');
        $this->assertEqualsWithDelta(60.0, $draft['lines'][4]['vat'], 0.001);
        $this->assertEqualsWithDelta(60.0, $draft['lines'][9]['vat'], 0.001, 'line 9 is total output tax');
        $this->assertEqualsWithDelta(150.0, $draft['lines'][14]['vat'], 0.001, 'standard purchases on line 14');
        $this->assertEqualsWithDelta(150.0, $draft['lines'][23]['vat'], 0.001, 'line 23 is total input rebate');
        $this->assertEqualsWithDelta(-90.0, $draft['net'], 0.001, 'line 35 = 9 − 23 + 28 − 34; negative carries forward');
    }

    public function test_reduced_rate_sale_goes_to_line_seven(): void
    {
        $this->sale($this->product('reduced', '5'), 100, 1);      // taxable 100, VAT 5

        $draft = $this->draft();

        $this->assertEqualsWithDelta(5.0, $draft['lines'][7]['vat'], 0.001);
        $this->assertEqualsWithDelta(5.0, $draft['lines'][9]['vat'], 0.001);
    }

    public function test_sales_credit_note_reduces_net_through_line_thirty_two(): void
    {
        $this->sale($this->product('standard'), 200, 2);          // VAT 60
        $order = Order::first();
        VatCreditNote::create([
            'credit_note_no' => 'CN-2026-000001', 'type' => 'sale', 'order_id' => $order->id,
            'taxable_value' => 400, 'vat_amount' => 60, 'issued_at' => '2026-10-20 10:00:00',
        ]);

        $draft = $this->draft();

        $this->assertEqualsWithDelta(60.0, $draft['lines'][32]['vat'], 0.001, 'credit note on line 32');
        $this->assertEqualsWithDelta(0.0, $draft['net'], 0.001, 'the reversal cancels the output tax');
    }

    public function test_a_product_without_a_category_is_reported_not_guessed(): void
    {
        $this->sale($this->product(''), 200, 1);

        $draft = $this->draft();

        $this->assertArrayHasKey('sales', $draft['unmapped'], 'an uncategorised product must not be placed on a line');
        $this->assertArrayNotHasKey(4, $draft['lines']);
    }
}
