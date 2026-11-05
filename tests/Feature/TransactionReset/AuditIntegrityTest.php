<?php

namespace Tests\Feature\TransactionReset;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\TransactionResetAudit;
use App\Models\User;
use App\Services\StockManagementService;
use App\Services\TransactionReset\SaleResetService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reset audit: written with the change, kept when the sale is deleted, not touched by the log archive, and
 * holding the original record and the outcome.
 */
class AuditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $this->admin = User::first();
        $this->actingAs($this->admin, 'admin');
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $category = Category::create(['name' => 'Aud', 'slug' => 'aud-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Audit item', 'slug' => 'aud-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'AU-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
    }

    private function order(string $status = 'completed'): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-AUD-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Audit item', 'purchase_price' => 100,
        ]);
        if ($status !== 'pending') {
            app(\App\Services\OrderStatusService::class)->handleStatusChange($order, 'pending', $status);
            $order->update(['order_status' => $status]);
        }

        return $order->fresh();
    }

    public function test_a_failed_audit_write_rolls_the_reset_back(): void
    {
        $order = $this->order('completed');

        try {
            // An IP longer than its column is refused by the database, so the audit row cannot be written.
            app(SaleResetService::class)->reverse($order, $this->admin, 'Audit must fail.', str_repeat('1', 60));
            $this->fail('the reset must fail when its audit cannot be written');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\DomainException::class, $e, 'the failure comes from the audit write itself');
        }

        $this->assertSame('completed', $order->fresh()->order_status, 'the status change was rolled back');
        $this->assertSame(8, (int) $this->product->fresh()->stock, 'the sale still holds its 2 units: nothing was restocked');
        $this->assertSame(0, TransactionResetAudit::where('order_id', $order->id)->count());
    }

    public function test_the_reversal_audit_records_who_why_what_and_the_outcome(): void
    {
        $order = $this->order('completed');

        $result = app(SaleResetService::class)->reverse($order, $this->admin, 'Customer changed mind.', '127.0.0.1');

        $audit = TransactionResetAudit::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('return', $audit->mode);
        $this->assertSame('reverse', $audit->action);
        $this->assertSame('completed', $audit->previous_status);
        $this->assertSame('returned', $audit->new_status);
        $this->assertSame('Customer changed mind.', $audit->reason);
        $this->assertSame($this->admin->id, (int) $audit->performed_by);
        $this->assertSame($this->admin->name, $audit->performed_by_name);
        $this->assertSame('127.0.0.1', $audit->ip);
        $this->assertSame('completed', $audit->snapshot['order']['order_status'], 'the sale as it was before the reversal');
        $this->assertCount(1, $audit->snapshot['lines']);
        $this->assertSame($result['accounting']['status'], $audit->accounting_outcome);
        $this->assertCount(1, $audit->reversal_summary['summary']['restocked']);
        $this->assertSame(2, $audit->reversal_summary['summary']['restocked'][0]['quantity']);
    }

    public function test_a_complete_delete_keeps_the_original_record_in_the_snapshot(): void
    {
        $order = $this->order('pending');
        $invoice = $order->invoice_id;
        app(SaleResetService::class)->reverse($order, $this->admin, 'Cancel before delete.');

        app(SaleResetService::class)->completeDelete($order->fresh(), $this->admin, 'Remove the test sale.');

        $this->assertNull(Order::find($order->id));
        $audit = TransactionResetAudit::where('order_id', $order->id)->where('action', 'delete')->firstOrFail();
        $this->assertSame($invoice, $audit->invoice_id);
        $this->assertSame($invoice, $audit->snapshot['order']['invoice_id']);
        $this->assertCount(1, $audit->snapshot['lines']);
        $this->assertSame(2, (int) $audit->snapshot['lines'][0]['qty']);
        $this->assertArrayHasKey('shipping', $audit->snapshot);
        $this->assertArrayHasKey('warranty_sales', $audit->snapshot);
    }

    public function test_the_log_archive_does_not_touch_reset_audits(): void
    {
        $order = $this->order('pending');
        app(SaleResetService::class)->reverse($order, $this->admin, 'Cancel before archive test.');
        $before = TransactionResetAudit::count();

        $this->artisan('logs:archive-activity', ['--days' => 0])->assertSuccessful();

        $this->assertSame($before, TransactionResetAudit::count());
    }
}
