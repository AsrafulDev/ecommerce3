<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\DamageProduct;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantySale;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Write-off of a damaged unit. A write-off is a loss, not a payment: it must not move the
 * till, must not restock or re-cost stock, must use the original batch cost when no cost is
 * entered, and must record once even if the status is saved twice.
 */
class DamageWriteOffTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private DamageProduct $damage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);

        // Till: 10,000 of owner capital.
        FundTransaction::create(['direction' => 'in', 'source' => 'manual_add', 'amount' => 10000, 'created_by' => 1]);

        // Stock: 5 units at a batch cost of 100. The product's current purchase price is set
        // to 999, to prove that the write-off never uses it.
        $category = \App\Models\Category::create(['name' => 'Dmg', 'slug' => 'dmg-' . uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Damage Item', 'slug' => 'dmg-item-' . uniqid(), 'category_id' => $category->id,
            'product_code' => 'DM-' . uniqid(), 'purchase_price' => 999, 'new_price' => 200, 'stock' => 0, 'status' => 1,
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 5, 'unit_cost' => 100]);
        $batchId = \App\Models\StockBatch::where('product_id', $this->product->id)->value('id');

        // The unit was sold from that batch, under a warranty sale.
        $customer = \App\Models\Customer::create(['name' => 'Dmg Customer', 'slug' => 'dmg-c-' . uniqid(), 'phone' => '01700000001', 'password' => bcrypt('x'), 'verify' => 1, 'status' => 'active']);
        $order = \App\Models\Order::create([
            'invoice_id' => 'INV-DMG-' . uniqid(), 'amount' => 200, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $customer->id, 'order_status' => 'completed', 'created_at' => now(),
        ]);
        $detail = \App\Models\OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 1,
            'product_name' => $this->product->name, 'purchase_price' => 100,
        ]);
        $sale = WarrantySale::create([
            'order_id' => $order->id, 'order_detail_id' => $detail->id, 'product_id' => $this->product->id,
            'customer_id' => $customer->id, 'warranty_type' => 'store_warranty', 'warranty_days' => 365, 'warranty_price' => 0,
            'is_transferable' => false, 'status' => 'active', 'stock_batch_id' => $batchId,
        ]);

        $this->damage = DamageProduct::create([
            'warranty_sale_id' => $sale->id, 'product_id' => $this->product->id,
            'damage_type' => 'full', 'status' => 'on_warranty', 'received_at' => now(),
        ]);
    }

    private function writeOff(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.warranty.damage.status', $this->damage->id), array_merge(['status' => 'unsellable'], $extra));
    }

    public function test_a_write_off_does_not_move_the_till(): void
    {
        $tillBefore = FundHelper::balance();
        $outBefore = FundTransaction::where('direction', 'out')->count();

        $this->writeOff(["damage_cost" => 500]);

        $this->assertEqualsWithDelta($tillBefore, FundHelper::balance(), 0.01, 'a write-off is non-cash');
        $this->assertSame($outBefore, FundTransaction::where('direction', 'out')->count(), 'no fund row is created');
        $expense = Expense::where('category', 'warranty_loss')->firstOrFail();
        $this->assertEqualsWithDelta(500.0, (float) $expense->amount, 0.01, 'the loss is recorded');
        $this->assertNull($expense->fund_transaction_id, 'the loss is not linked to any cash movement');
    }

    public function test_without_an_entered_cost_the_original_batch_cost_is_used_not_the_product_price(): void
    {
        $this->writeOff();

        $expense = Expense::where('category', 'warranty_loss')->firstOrFail();
        $this->assertEqualsWithDelta(100.0, (float) $expense->amount, 0.01, 'batch cost 100, not product price 999');
    }

    public function test_stock_is_not_changed_by_the_write_off(): void
    {
        $stockBefore = (int) $this->product->fresh()->stock;
        $valueBefore = (float) app(StockManagementService::class)->getCurrentValuation($this->product->fresh());

        $this->writeOff(['damage_cost' => 500]);

        $this->assertSame($stockBefore, (int) $this->product->fresh()->stock, 'the unit was not in sellable stock, so nothing is restocked or removed');
        $this->assertEqualsWithDelta($valueBefore, app(StockManagementService::class)->getCurrentValuation($this->product->fresh()), 0.01);
    }

    public function test_saving_the_write_off_twice_records_one_loss(): void
    {
        $this->writeOff(['damage_cost' => 500]);
        $this->writeOff(['damage_cost' => 500]);

        $this->assertSame(1, Expense::where('category', 'warranty_loss')->count(), 'a repeat save must not record a second loss');
    }

    public function test_a_changed_cost_updates_the_loss_once_and_still_moves_no_cash(): void
    {
        $tillBefore = FundHelper::balance();

        $this->writeOff(['damage_cost' => 500]);
        $this->writeOff(['damage_cost' => 650]);

        $this->assertSame(1, Expense::where('category', 'warranty_loss')->count());
        $this->assertEqualsWithDelta(650.0, (float) Expense::where('category', 'warranty_loss')->value('amount'), 0.01);
        $this->assertEqualsWithDelta($tillBefore, FundHelper::balance(), 0.01);
    }

    public function test_no_journal_is_posted_for_the_write_off_until_its_account_is_approved(): void
    {
        $this->writeOff(['damage_cost' => 500]);

        $this->assertSame(0, \Softmit\DoubleEntry\Models\JournalEntry::count(), 'the Advanced posting waits for approval of the damage account');
    }
}
