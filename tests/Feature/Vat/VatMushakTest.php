<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\Vat\VatCalculator;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatMushakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DefaultDatabaseSeeder::class);
    }

    private function switches(?bool $enabled, ?bool $inclusive): void
    {
        GeneralSetting::query()->update([
            'vat_enabled' => $enabled ?? false,
            'vat_prices_include_vat' => $inclusive,
        ]);
    }

    private function order(): Order
    {
        return Order::create([
            'invoice_id' => 'INV-H-' . uniqid(), 'amount' => 0, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => '2026-10-02 10:00:00',
        ]);
    }

    private function product(?float $rate): Product
    {
        return Product::create([
            'name' => 'VAT Product', 'slug' => 'vat-p-' . uniqid(), 'category_id' => 1,
            'product_code' => 'VP-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200,
            'stock' => 0, 'status' => 1, 'vat_rate' => $rate,
        ]);
    }

    public function test_split_inclusive_and_exclusive_round_to_two_decimals(): void
    {
        $calc = new VatCalculator();

        $this->assertSame(['taxable' => 100.0, 'vat' => 15.0], $calc->split(115, 15, true));
        $this->assertSame(['taxable' => 100.0, 'vat' => 15.0], $calc->split(100, 15, false));
        $this->assertSame(['taxable' => 86.95, 'vat' => 13.04], $calc->split(99.99, 15, true));
    }

    public function test_negative_rate_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new VatCalculator())->split(100, -5, false);
    }

    public function test_vat_stays_off_until_enabled_and_inclusive_choice_is_made(): void
    {
        $calc = new VatCalculator();

        $this->switches(false, null);
        $this->assertFalse($calc->enabled());

        $this->switches(true, null);
        $this->assertFalse($calc->enabled(), 'enabled without the inclusive choice must stay off');

        $this->switches(true, false);
        $this->assertTrue($calc->enabled());
    }

    public function test_sale_line_gets_no_vat_while_switched_off(): void
    {
        $this->switches(false, null);
        $product = $this->product(15);

        $detail = OrderDetails::create(['order_id' => $this->order()->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 2, 'product_name' => 'x', 'purchase_price' => 100]);

        $this->assertNull($detail->fresh()->vat_amount);
    }

    public function test_sale_line_snapshot_is_taken_at_creation_when_enabled(): void
    {
        $this->switches(true, false);
        $product = $this->product(15);

        $detail = OrderDetails::create(['order_id' => $this->order()->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 2, 'product_name' => 'x', 'purchase_price' => 100]);
        $fresh = $detail->fresh();

        $this->assertEqualsWithDelta(15.0, (float) $fresh->vat_rate, 0.001);
        $this->assertEqualsWithDelta(400.0, (float) $fresh->taxable_value, 0.001);
        $this->assertEqualsWithDelta(60.0, (float) $fresh->vat_amount, 0.001);

        // A later rate change must not rewrite the snapshot.
        $product->update(['vat_rate' => 5]);
        $this->assertEqualsWithDelta(60.0, (float) $detail->fresh()->vat_amount, 0.001);
    }

    public function test_product_without_a_rate_gets_no_snapshot(): void
    {
        $this->switches(true, true);
        $detail = OrderDetails::create(['order_id' => $this->order()->id, 'product_id' => $this->product(null)->id, 'sale_price' => 200, 'qty' => 1, 'product_name' => 'x', 'purchase_price' => 100]);

        $this->assertNull($detail->fresh()->vat_amount);
    }

    public function test_purchase_line_snapshot_and_monthly_return(): void
    {
        $this->switches(true, false);
        $product = $this->product(15);
        $supplier = Supplier::create(['name' => 'VAT Supplier', 'bin' => '000111222333']);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id, 'invoice_no' => 'PUR-VAT-1', 'purchase_date' => '2026-10-05',
            'amount' => 1000, 'grand_total' => 1000, 'paid_amount' => 0, 'due_amount' => 1000, 'created_by' => 1,
        ]);
        $item = PurchaseItem::create([
            'purchase_id' => $purchase->id, 'product_id' => $product->id, 'qty' => 5,
            'unit_cost' => 200, 'line_total' => 1000,
        ]);
        $this->assertEqualsWithDelta(150.0, (float) $item->fresh()->vat_amount, 0.001, 'input VAT on 1000 exclusive at 15%');

        $order = Order::create([
            'invoice_id' => 'INV-VAT-1', 'amount' => 460, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => '2026-10-07 10:00:00',
        ]);
        OrderDetails::create(['order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 2, 'product_name' => 'x', 'purchase_price' => 100]);

        $report = new VatReportService();
        $from = Carbon::parse('2026-10-01');
        $to = Carbon::parse('2026-10-31');

        $sales = $report->salesRegister($from, $to);
        $this->assertCount(1, $sales);
        $this->assertSame('INV-VAT-1', $sales[0]['invoice']);
        $this->assertEqualsWithDelta(60.0, $sales[0]['vat'], 0.001);

        $purchases = $report->purchaseRegister($from, $to);
        $this->assertSame('000111222333', $purchases[0]['supplier_bin']);
        $this->assertEqualsWithDelta(150.0, $purchases[0]['vat'], 0.001);

        $month = $report->monthReturn($from, $to);
        $this->assertEqualsWithDelta(60.0, $month['output_vat'], 0.001);
        $this->assertEqualsWithDelta(150.0, $month['input_vat'], 0.001);
        $this->assertEqualsWithDelta(-90.0, $month['net'], 0.001, 'a negative net is a carry-forward');
        $this->assertSame(0, $month['sales_lines_without_vat']);
    }

    public function test_returned_and_pending_sales_are_left_out_of_output_vat(): void
    {
        $this->switches(true, false);
        $product = $this->product(15);

        $returned = Order::create([
            'invoice_id' => 'INV-RET', 'amount' => 230, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'returned', 'created_at' => '2026-10-08 10:00:00',
        ]);
        OrderDetails::create(['order_id' => $returned->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 1, 'product_name' => 'x', 'purchase_price' => 100]);

        $month = (new VatReportService())->monthReturn(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEqualsWithDelta(0.0, $month['output_vat'], 0.001);
    }
}
