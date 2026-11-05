<?php

namespace Tests\Feature\TransactionReset;

use App\Helpers\FundHelper;
use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use App\Services\TransactionReset\SaleAccountingVerifier;
use App\Services\TransactionReset\SaleResetService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Tests\TestCase;

/**
 * The reset refuses a sale whose money, courier consignment or posted journals it cannot reverse with certainty.
 */
class AccountingFundCourierTest extends TestCase
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

        $category = Category::create(['name' => 'Acc', 'slug' => 'acc-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Acc item', 'slug' => 'acc-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'AC-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
    }

    private function order(string $status = 'pending'): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-AC-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => 1, 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Acc item', 'purchase_price' => 100,
        ]);
        if ($status !== 'pending') {
            app(OrderStatusService::class)->handleStatusChange($order, 'pending', $status);
            $order->update(['order_status' => $status]);
        }

        return $order->fresh();
    }

    private function reverse(Order $order): array
    {
        return app(SaleResetService::class)->reverse($order, $this->admin, 'Accounting test.');
    }

    public function test_a_sale_with_no_journals_reports_accounts_not_applicable(): void
    {
        $result = $this->reverse($this->order('completed'));

        $this->assertSame(SaleAccountingVerifier::NOT_APPLICABLE, $result['accounting']['status']);
    }

    public function test_a_posted_sale_journal_blocks_the_reset_while_accounting_is_not_ready(): void
    {
        config(['double-entry.enabled' => true]);
        $order = $this->order('pending');
        $this->postSaleJournal($order);

        try {
            $this->reverse($order);
            $this->fail('the reset must not run while the journal cannot be reversed');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('not ready', $e->getMessage());
        }

        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame(10, (int) $this->product->fresh()->stock);
    }

    public function test_the_books_are_checked_after_the_reversal_and_a_still_posted_journal_is_refused(): void
    {
        config(['double-entry.enabled' => true]);
        $order = $this->order('pending');
        $journal = $this->postSaleJournal($order);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('still posted');
        app(SaleAccountingVerifier::class)->verifyReversed($order, [$journal]);
    }

    public function test_a_sale_with_cash_in_the_ledger_is_refused(): void
    {
        $order = $this->order('completed');
        FundHelper::creditSale($order, 'Test cash for the sale');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cash ledger holds');
        $this->reverse($order);
    }

    public function test_a_sale_with_a_courier_consignment_out_of_the_shop_is_refused(): void
    {
        $order = $this->order('pending');
        $order->update(['courier_tracking_id' => 'RX-TEST-1']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('courier consignment');
        $this->reverse($order->fresh());
    }

    /** A posted sale journal for the order. A fixture for the verifier's refusals, not a real posting. */
    private function postSaleJournal(Order $order): JournalEntry
    {
        return JournalEntry::create([
            'journal_no' => 'JE-TEST-'.uniqid(),
            'transaction_date' => now()->toDateString(),
            'status' => JournalStatus::POSTED->value,
            'source_type' => SourceType::SALE->value,
            'source_id' => $order->id,
            'posting_key' => 'sale:'.$order->id.':'.uniqid(),
            'description' => 'Test sale journal',
        ]);
    }
}
