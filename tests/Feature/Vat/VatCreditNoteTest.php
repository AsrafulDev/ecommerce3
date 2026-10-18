<?php

namespace Tests\Feature\Vat;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\VatCreditNote;
use App\Services\OrderStatusService;
use App\Services\Vat\VatCreditNoteService;
use App\Services\Vat\VatReportService;
use Carbon\Carbon;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        GeneralSetting::query()->update(['vat_enabled' => true, 'vat_prices_include_vat' => false]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'CN Product', 'slug' => 'cn-' . uniqid(), 'category_id' => 1,
            'product_code' => 'CN-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200,
            'stock' => 0, 'status' => 1, 'vat_rate' => 15,
        ]);
    }

    /** A delivered sale of 2 × 200 at 15% exclusive: taxable 400, VAT 60. */
    private function completedSale(Product $product, string $createdAt): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-CN-' . uniqid(), 'amount' => 460, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'completed', 'created_at' => $createdAt,
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'CN Product', 'purchase_price' => 100,
        ]);

        return $order->fresh();
    }

    private function range(string $from, string $to): array
    {
        return [Carbon::parse($from), Carbon::parse($to)];
    }

    public function test_return_in_a_later_month_reverses_output_vat_in_that_month(): void
    {
        $order = $this->completedSale($this->product(), '2026-10-07 10:00:00');

        app(OrderStatusService::class)->handleStatusChange($order, 'completed', 'returned');

        $note = VatCreditNote::where('order_id', $order->id)->firstOrFail();
        $this->assertEqualsWithDelta(60.0, (float) $note->vat_amount, 0.001);
        // Move the return into November to test the cross-period rule.
        $note->update(['issued_at' => '2026-11-03 12:00:00']);

        $report = new VatReportService();
        [$oct1, $oct31] = $this->range('2026-10-01', '2026-10-31');
        [$nov1, $nov30] = $this->range('2026-11-01', '2026-11-30');

        $october = $report->monthReturn($oct1, $oct31);
        $november = $report->monthReturn($nov1, $nov30);

        $this->assertEqualsWithDelta(60.0, $october['output_vat'], 0.001, 'the sale stays declared in October');
        $this->assertEqualsWithDelta(-60.0, $november['output_vat'], 0.001, 'the return reduces November');
    }

    public function test_return_in_the_same_month_nets_to_zero(): void
    {
        $order = $this->completedSale($this->product(), '2026-10-07 10:00:00');
        app(OrderStatusService::class)->handleStatusChange($order, 'completed', 'returned');

        [$from, $to] = $this->range('2026-10-01', '2026-10-31');
        $this->assertEqualsWithDelta(0.0, (new VatReportService())->monthReturn($from, $to)['output_vat'], 0.001);
    }

    public function test_credit_note_is_issued_once_per_sale_and_numbered(): void
    {
        $order = $this->completedSale($this->product(), '2026-10-07 10:00:00');
        $service = app(VatCreditNoteService::class);

        $first = $service->forOrder($order, 'Returned');
        $second = $service->forOrder($order, 'Returned again');

        $this->assertNotNull($first);
        $this->assertNull($second, 'a second credit note for the same sale must not be issued');
        $this->assertSame(1, VatCreditNote::where('order_id', $order->id)->count());
        $this->assertMatchesRegularExpression('/^CN-\d{4}-000001$/', $first->credit_note_no);
    }

    public function test_cancelling_a_pending_order_issues_no_credit_note(): void
    {
        $order = Order::create([
            'invoice_id' => 'INV-PEND', 'amount' => 460, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => '2026-10-07 10:00:00',
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product()->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'x', 'purchase_price' => 100,
        ]);

        app(OrderStatusService::class)->handleStatusChange($order, 'pending', 'cancelled');

        $this->assertSame(0, VatCreditNote::count(), 'a sale that never happened has no VAT to reverse');
    }

    public function test_purchase_return_reduces_input_vat_pro_rata(): void
    {
        $product = $this->product();
        $purchase = Purchase::create([
            'supplier_id' => null, 'invoice_no' => 'PUR-CN-1', 'purchase_date' => '2026-10-05',
            'amount' => 1000, 'grand_total' => 1000, 'paid_amount' => 0, 'due_amount' => 1000, 'created_by' => 1,
        ]);
        $item = PurchaseItem::create([
            'purchase_id' => $purchase->id, 'product_id' => $product->id, 'qty' => 5,
            'unit_cost' => 200, 'line_total' => 1000,
        ]);
        $this->assertEqualsWithDelta(150.0, (float) $item->fresh()->vat_amount, 0.001);

        $note = app(VatCreditNoteService::class)->forPurchaseReturn($item->fresh(), 2, 1);

        $this->assertEqualsWithDelta(60.0, (float) $note->vat_amount, 0.001, '2 of 5 units returned: 2/5 of 150');
        $this->assertEqualsWithDelta(400.0, (float) $note->taxable_value, 0.001);

        [$from, $to] = $this->range('2026-10-01', '2026-10-31');
        $month = (new VatReportService())->monthReturn($from, $to);
        $this->assertEqualsWithDelta(90.0, $month['input_vat'], 0.001, 'input VAT is 150 less the 60 returned');
    }
}
