<?php

namespace Tests\Feature\Audit;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\WarrantySale;
use App\Services\StockManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The legacy audit finds each kind of disagreement it reports, and writes nothing: the key tables are the same before and
 * after it runs.
 */
class SalesLegacyAuditTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private int $batchId;

    private int $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = Customer::create([
            'name' => 'Audit buyer', 'slug' => 'audit-buyer-'.uniqid(), 'phone' => '01700000020',
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;

        $category = Category::create(['name' => 'Audit', 'slug' => 'audit-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Audit item', 'slug' => 'audit-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'AU-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        $this->batchId = app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100])->id;
    }

    private function order(string $status, array $lines, ?string $courierTracking = null): Order
    {
        $order = Order::create([
            'invoice_id' => 'AUD-'.uniqid(), 'amount' => 200, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->buyer, 'order_status' => $status, 'created_at' => now(),
            'courier_tracking_id' => $courierTracking,
        ]);

        foreach ($lines as $line) {
            OrderDetails::create([
                'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => $line['qty'],
                'product_name' => 'Audit item', 'purchase_price' => 100, 'batch_ids' => $line['batch_ids'] ?? null,
            ]);
        }

        return $order->fresh();
    }

    /** One batch allocation covering the whole line. */
    private function allocated(int $qty): array
    {
        return [['batch_id' => $this->batchId, 'qty' => $qty, 'unit_cost' => 100]];
    }

    /** Checksum of the tables a write would change. */
    private function snapshot(): string
    {
        $tables = ['orders', 'order_details', 'stock_batches', 'products', 'warranty_sales', 'fund_transactions'];

        return md5(json_encode(array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->all(), $tables)));
    }

    /** The count the report gives for one check, read from the printed table. */
    private function found(string $output, string $label): int
    {
        $this->assertMatchesRegularExpression('/'.preg_quote($label, '/').'\s*\|\s*\d+/', $output, "the report lists '{$label}'");
        preg_match('/'.preg_quote($label, '/').'\s*\|\s*(\d+)/', $output, $m);

        return (int) $m[1];
    }

    public function test_each_legacy_fault_is_reported_once_and_nothing_is_written(): void
    {
        // A: active, no allocation recorded at all.
        $this->order('confirmed', [['qty' => 1]]);
        // B: active, allocation that does not add up to the 2 sold.
        $this->order('confirmed', [['qty' => 2, 'batch_ids' => $this->allocated(1)]]);
        // C: cancelled after taking stock out, with no restock batch for the order.
        $this->order('cancelled', [['qty' => 1, 'batch_ids' => $this->allocated(1)]]);
        // D: active, and its serial is not listed as sold in any batch.
        $sold = $this->order('confirmed', [['qty' => 1, 'batch_ids' => $this->allocated(1)]]);
        WarrantySale::create([
            'order_id' => $sold->id, 'order_detail_id' => OrderDetails::where('order_id', $sold->id)->value('id'),
            'customer_id' => $this->buyer, 'product_id' => $this->product->id, 'serial_numbers' => ['ZZ-9'],
            'warranty_type' => 'serial', 'status' => 'active',
        ]);
        // E: returned, still carrying a courier consignment.
        $this->order('returned', [], 'RX-AUD-1');
        // P: the product's stock is not the sum of its batches.
        Product::whereKey($this->product->id)->update(['stock' => 999]);

        $before = $this->snapshot();

        Artisan::call('sales:legacy-audit', ['--sample' => 5]);
        $output = Artisan::output();

        $this->assertSame(1, $this->found($output, 'Active sales with no recorded stock allocation'));
        $this->assertSame(1, $this->found($output, 'Active sales whose recorded allocation does not match the quantity'));
        $this->assertSame(1, $this->found($output, 'Active warranty sales with a serial not recorded as sold in any batch'));
        $this->assertSame(1, $this->found($output, 'Cancelled or returned sales with an allocation but no restock'));
        $this->assertSame(1, $this->found($output, 'Products whose stock differs from their batches'));
        $this->assertSame(1, $this->found($output, 'Cancelled or returned sales that still carry a courier consignment'));
        $this->assertStringContainsString('Read-only: nothing was changed.', $output);

        $this->assertSame($before, $this->snapshot(), 'the audit wrote nothing');
    }

    public function test_a_clean_database_reports_nothing(): void
    {
        $this->order('completed', [['qty' => 1, 'batch_ids' => $this->allocated(1)]]);

        Artisan::call('sales:legacy-audit');
        $output = Artisan::output();

        foreach ([
            'Active sales with no recorded stock allocation',
            'Active sales whose recorded allocation does not match the quantity',
            'Active warranty sales with a serial not recorded as sold in any batch',
            'Cancelled or returned sales with an allocation but no restock',
            'Products whose stock differs from their batches',
            'Cancelled or returned sales that still carry a courier consignment',
        ] as $label) {
            $this->assertSame(0, $this->found($output, $label), $label);
        }
    }
}
