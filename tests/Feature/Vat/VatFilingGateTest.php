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
use App\Services\Vat\VatCalculator;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatFilingGateTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $from;
    private Carbon $to;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->from = Carbon::parse('2026-10-01');
        $this->to = Carbon::parse('2026-10-31');
    }

    private function product(?string $category, ?float $rate = 15): Product
    {
        return Product::create([
            'name' => 'Gate ' . uniqid(), 'slug' => 'g-' . uniqid(), 'category_id' => 1, 'product_code' => 'G-' . uniqid(),
            'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'vat_rate' => $rate, 'vat_category' => $category,
        ]);
    }

    private function sale(Product $product, string $createdAt, float $price = 200): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-G-' . uniqid(), 'amount' => $price, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => $createdAt,
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => $price, 'qty' => 1,
            'product_name' => $product->name, 'purchase_price' => 100,
        ]);

        return $order;
    }

    /** Settings and configuration that satisfy every blocker except data gaps. */
    private function fullyDecided(): void
    {
        GeneralSetting::query()->update([
            'vat_enabled' => true, 'vat_prices_include_vat' => false, 'vat_bin' => '123456789012',
        ]);
        config(['vat.filing_frequency' => 'monthly', 'vat.mapping_approved' => true]);
    }

    private function draft(): array
    {
        return (new Mushak91Draft())->build($this->from, $this->to, new VatReportService());
    }

    public function test_by_default_every_return_is_a_draft_with_the_open_decisions_listed(): void
    {
        $draft = $this->draft();

        $this->assertSame('draft', $draft['status']);
        $this->assertTrue($draft['draft']);
        $this->assertStringContainsString('switched off', implode(' ', $draft['blockers']));
        $this->assertStringContainsString('filing frequency', implode(' ', $draft['blockers']));
        $this->assertStringContainsString('practitioner', implode(' ', $draft['blockers']));
    }

    public function test_a_return_becomes_final_only_when_every_decision_and_check_is_met(): void
    {
        $this->fullyDecided();
        $product = $this->product('standard');
        $this->sale($product, '2026-10-07 10:00:00');

        $draft = $this->draft();

        $this->assertSame([], $draft['blockers']);
        $this->assertSame('final', $draft['status']);
    }

    public function test_an_unknown_category_blocks_the_return_and_is_not_placed_on_a_line(): void
    {
        $this->fullyDecided();
        $this->sale($this->product('mystery_category'), '2026-10-07 10:00:00');

        $draft = $this->draft();

        $this->assertSame('draft', $draft['status']);
        $this->assertArrayHasKey('sales', $draft['unmapped']);
        $this->assertNotEmpty($draft['blockers']);
    }

    public function test_a_credit_note_in_the_period_blocks_final_until_it_is_split_by_category(): void
    {
        $this->fullyDecided();
        $product = $this->product('standard');
        $order = $this->sale($product, '2026-10-07 10:00:00');
        VatCreditNote::create([
            'credit_note_no' => 'CN-2026-000009', 'type' => 'sale', 'order_id' => $order->id,
            'taxable_value' => 200, 'vat_amount' => 30, 'issued_at' => '2026-10-15 10:00:00',
        ]);

        $draft = $this->draft();

        $this->assertSame('draft', $draft['status']);
        $this->assertStringContainsString('Credit notes', implode(' ', $draft['blockers']));
    }

    public function test_a_missing_frequency_alone_keeps_the_return_a_draft(): void
    {
        $this->fullyDecided();
        config(['vat.filing_frequency' => null]);
        $this->sale($this->product('standard'), '2026-10-07 10:00:00');

        $this->assertSame('draft', $this->draft()['status']);
    }

    public function test_a_sale_on_the_last_second_of_the_month_is_in_that_month_and_the_first_second_after_is_not(): void
    {
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
        $product = $this->product('standard');
        $this->sale($product, '2026-10-31 23:59:59');
        $this->sale($product, '2026-11-01 00:00:00');

        $reports = new VatReportService();
        $october = $reports->salesRegister($this->from, $this->to);
        $november = $reports->salesRegister(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'));

        $this->assertCount(1, $october, 'the 31 Oct sale is October');
        $this->assertCount(1, $november, 'the 1 Nov sale is November');
    }

    public function test_vat_rounds_to_two_decimals_per_line_with_half_up(): void
    {
        $calc = new VatCalculator();

        $this->assertSame(0.0, $calc->split(0.01, 15, false)['vat'], '0.0015 rounds to 0.00');
        $this->assertSame(0.02, $calc->split(0.10, 15, false)['vat'], '0.015 rounds half up to 0.02');
        $this->assertSame(0.0, $calc->split(0.03, 15, false)['vat'], '0.0045 rounds to 0.00');
    }
}
