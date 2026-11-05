<?php

namespace Tests\Feature\TransactionReset;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Refund;
use App\Models\StockBatch;
use App\Models\TransactionResetAudit;
use App\Models\User;
use App\Models\VatCreditNote;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use App\Services\TransactionReset\SaleResetService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reversal rules: what each sale state does, what blocks it, that restock uses the recorded batch cost,
 * that a sale is restocked only once, and that every change leaves a durable audit row.
 */
class SaleReversalTest extends TestCase
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
        $this->admin->update(['password' => bcrypt('password')]);
        $this->actingAs($this->admin, 'admin');
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $category = Category::create(['name' => 'Rev', 'slug' => 'rev-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Rev item', 'slug' => 'rev-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'RV-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);
        // Stock comes in at 100. The catalogue cost then moves to 150, so a restock at the current price is visible.
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
        // FIFO: a sale is charged the cost of the batch it came from, so the restock must use that batch cost.
        $this->product->update(['purchase_price' => 150, 'costing_method' => 'fifo']);
    }

    /** A sale in the given state, taken through the normal stock-out path when the state holds stock. */
    private function sale(string $status = 'completed', int $qty = 2): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-'.uniqid(), 'amount' => 200 * $qty, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => $qty,
            'product_name' => 'Rev item', 'purchase_price' => 150,
        ]);
        if ($status !== 'pending') {
            app(OrderStatusService::class)->handleStatusChange($order, 'pending', $status);
            $order->update(['order_status' => $status]);
        }

        return $order->fresh();
    }

    private function reverse(Order $order): array
    {
        return app(SaleResetService::class)->reverse($order, $this->admin, 'Reset for the reversal test.');
    }

    public function test_a_completed_sale_goes_through_the_return_steps_and_is_restocked_at_its_batch_cost(): void
    {
        $order = $this->sale('completed');

        $result = $this->reverse($order);

        $this->assertSame('return', $result['mode']);
        $this->assertSame('returned', $order->fresh()->order_status);
        $this->assertSame(10, (int) $this->product->fresh()->stock);
        $line = OrderDetails::where('order_id', $order->id)->firstOrFail();
        $this->assertNull($line->batch_ids, 'the line no longer claims the batches it was restocked to');
        $this->assertNull($line->cogs, 'the line COGS is cleared with the restock');
        $restock = StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->get();
        $this->assertSame([100.0], $restock->pluck('unit_cost')->map(fn ($c) => (float) $c)->unique()->values()->all(),
            'restocked at the batch cost (100), not the current catalogue cost (150)');
    }

    public function test_under_average_costing_the_restock_uses_the_cost_the_sale_recorded(): void
    {
        $this->product->update(['costing_method' => 'average']);
        $order = $this->sale('completed');
        $recorded = (float) OrderDetails::where('order_id', $order->id)->first()->batch_ids[0]['unit_cost'];

        $this->reverse($order);

        $restock = StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->firstOrFail();
        $this->assertEqualsWithDelta($recorded, (float) $restock->unit_cost, 0.001, 'the cost charged to the sale is the cost restored');
    }

    public function test_a_delivered_sale_is_reversed_the_same_way(): void
    {
        $order = $this->sale('delivered');

        $this->assertSame('return', $this->reverse($order)['mode']);
        $this->assertSame('returned', $order->fresh()->order_status);
    }

    public function test_a_sale_not_yet_shipped_is_cancelled_and_its_stock_comes_back(): void
    {
        $order = $this->sale('pending');

        $result = $this->reverse($order);

        $this->assertSame('cancel', $result['mode']);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, (int) $this->product->fresh()->stock, 'a pending sale never took stock');
    }

    public function test_an_already_cancelled_sale_is_refused(): void
    {
        $order = $this->sale('pending');
        $this->reverse($order);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Already cancelled.');
        $this->reverse($order->fresh());
    }

    public function test_a_second_reversal_of_a_returned_sale_is_refused_and_restocks_nothing(): void
    {
        $order = $this->sale('completed');
        $this->reverse($order);

        try {
            $this->reverse($order->fresh());
            $this->fail('the second reversal should be refused');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Already returned', $e->getMessage());
        }

        $this->assertSame(10, (int) $this->product->fresh()->stock, 'stock was not added twice');
        $this->assertSame(1, StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count());
    }

    public function test_an_existing_refund_blocks_the_reversal(): void
    {
        $order = $this->sale('completed');
        Refund::create([
            'order_id' => $order->id, 'customer_id' => 1, 'refund_id' => 'RF-'.uniqid(), 'amount' => 100,
            'refund_amount' => 100, 'reason' => 'damaged', 'status' => 'pending',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('refund');
        $this->reverse($order);
    }

    public function test_an_existing_vat_credit_note_blocks_the_reversal(): void
    {
        $order = $this->sale('completed');
        VatCreditNote::create([
            'credit_note_no' => 'CN-'.uniqid(), 'type' => 'sale', 'order_id' => $order->id, 'taxable_value' => 400,
            'vat_amount' => 60, 'reason' => 'earlier', 'issued_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('VAT credit note');
        $this->reverse($order);
    }

    public function test_a_missing_batch_allocation_blocks_and_changes_nothing(): void
    {
        $order = $this->sale('completed');
        OrderDetails::where('order_id', $order->id)->update(['batch_ids' => null]);
        $stockBefore = (int) $this->product->fresh()->stock;

        try {
            $this->reverse($order);
            $this->fail('a sale without its batch record must not be reversed');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('not recorded', $e->getMessage());
        }

        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertSame($stockBefore, (int) $this->product->fresh()->stock);
    }

    public function test_a_batch_allocation_that_does_not_add_up_blocks_the_reversal(): void
    {
        $order = $this->sale('completed');
        $line = OrderDetails::where('order_id', $order->id)->first();
        $line->update(['batch_ids' => [['batch_id' => 1, 'qty' => 1, 'unit_cost' => 100]]]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('do not add up');
        $this->reverse($order);
    }

    public function test_a_sale_with_a_warranty_serial_and_no_batch_cannot_be_reversed(): void
    {
        $order = $this->sale('completed');
        $buyer = \App\Models\Customer::create([
            'name' => 'Warranty buyer', 'slug' => 'wb-'.uniqid(), 'phone' => '01700000001',
            'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ]);
        \App\Models\WarrantySale::create([
            'order_id' => $order->id, 'order_detail_id' => OrderDetails::where('order_id', $order->id)->value('id'),
            'customer_id' => $buyer->id, 'product_id' => $this->product->id, 'serial_numbers' => ['SN-1'],
            'warranty_type' => 'serial', 'stock_batch_id' => null,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not recorded as sold');
        $this->reverse($order);
    }

    public function test_a_complaint_blocks_a_complete_delete(): void
    {
        $order = $this->sale('pending');
        $this->reverse($order);
        Complaint::create([
            'customer_id' => 1, 'order_id' => $order->id, 'name' => 'Buyer', 'phone' => '01000000000',
            'description' => 'Late', 'status' => 'open',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('complaint');
        app(SaleResetService::class)->completeDelete($order->fresh(), $this->admin, 'Reset for the delete test.');
    }

    public function test_a_complete_delete_leaves_a_durable_audit_row_with_the_full_record(): void
    {
        $order = $this->sale('pending');
        $invoice = $order->invoice_id;
        $this->reverse($order);

        app(SaleResetService::class)->completeDelete($order->fresh(), $this->admin, 'Reset for the delete test.', '127.0.0.1');

        $this->assertNull(Order::find($order->id));
        $audit = TransactionResetAudit::where('order_id', $order->id)->where('action', 'delete')->firstOrFail();
        $this->assertSame($invoice, $audit->invoice_id);
        $this->assertSame('complete_delete', $audit->mode);
        $this->assertCount(1, $audit->snapshot['lines']);
        $this->assertSame('Reset for the delete test.', $audit->reason);
    }

    public function test_a_bulk_reset_reports_the_failures_and_still_reverses_the_rest(): void
    {
        $good = $this->sale('pending');
        $blocked = $this->sale('completed');
        $this->reverse($blocked);

        $response = $this->post(route('admin.transaction-reset.act'), [
            'action' => 'cancel', 'order_ids' => [$good->id, $blocked->id],
            'reason' => 'Bulk reset for the test.', 'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertSame('cancelled', $good->fresh()->order_status);
        $this->assertSame('returned', $blocked->fresh()->order_status);
        $this->assertStringStartsWith('1 sale(s) reversed.', session('success'));
    }

    public function test_a_user_without_the_permission_cannot_open_the_page(): void
    {
        $this->actingAs(User::create([
            'name' => 'No Rights', 'email' => 'norights-'.uniqid().'@example.test',
            'password' => bcrypt('password'), 'status' => 1,
        ]), 'admin');

        $this->get(route('admin.transaction-reset.index'))->assertForbidden();
    }
}
