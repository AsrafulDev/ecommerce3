<?php

namespace Tests\Feature;

use App\Models\FundTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseLog;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1.1 (UPDATE-PLAN) — PurchaseController::store() must publish inside ONE
 * DB transaction: a mid-loop failure (e.g. stock error on line 2) rolls back
 * EVERYTHING (header, items, batches, supplier due, fund, audit log) so no
 * partial purchase can exist.
 */
class PurchaseStoreTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');

        // Keep batch-wise pricing OFF so `products.stock` reads the raw column
        // (same as StockServiceTest) instead of the website-stock accessor.
        config(['pricing.batch_wise' => false]);
    }

    protected function makeProduct(string $code): Product
    {
        return Product::create([
            'name'           => 'Purchase Tx Product '.$code,
            'slug'           => 'purchase-tx-'.$code.'-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => $code,
            'purchase_price' => 50,
            'new_price'      => 100,
            'stock'          => 0,
            'status'         => 1,
        ]);
    }

    protected function makeSupplier(): Supplier
    {
        return Supplier::create(['name' => 'Tx Supplier '.uniqid()]);
    }

    protected function validPayload(Supplier $supplier, array $items): array
    {
        return [
            'supplier_id'   => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'invoice_no'    => 'INV-TX-'.uniqid(),
            'items'         => $items,
            'paid_amount'   => 100,
        ];
    }

    public function test_store_creates_purchase_atomically_on_success(): void
    {
        $supplier = $this->makeSupplier();
        $p1 = $this->makeProduct('TXA');
        $p2 = $this->makeProduct('TXB');

        // 5 x 40 + 3 x 60 = 380 grand total, 100 paid, 280 due
        $response = $this->post(route('purchases.store'), $this->validPayload($supplier, [
            ['product_id' => $p1->id, 'qty' => 5, 'unit_cost' => 40],
            ['product_id' => $p2->id, 'qty' => 3, 'unit_cost' => 60],
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $purchase = Purchase::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->assertNotNull($purchase, 'purchase should have been created');
        $this->assertSame(380.0, (float) $purchase->grand_total);
        $this->assertSame(100.0, (float) $purchase->paid_amount);
        $this->assertSame(280.0, (float) $purchase->due_amount);

        // Stock moved via stockIn → batches created + denormalized stock synced.
        $this->assertSame(2, StockBatch::where('purchase_id', $purchase->id)->where('type', 'in')->count());
        $this->assertSame(5, (int) $p1->fresh()->stock);
        $this->assertSame(3, (int) $p2->fresh()->stock);

        // Supplier due + fund payment + audit log all recorded.
        $this->assertSame(280.0, (float) $supplier->fresh()->current_due);
        $this->assertSame(1, FundTransaction::where('source', 'supplier_payment')->where('amount', 100)->count());
        $this->assertSame(1, SupplierPayment::where('purchase_id', $purchase->id)->count());
        $this->assertSame(1, PurchaseLog::where('purchase_id', $purchase->id)->where('action', 'create')->count());
        $this->assertSame(2, PurchaseItem::where('purchase_id', $purchase->id)->count());
    }

    public function test_store_rolls_back_everything_when_a_mid_loop_step_fails(): void
    {
        $supplier = $this->makeSupplier();
        $p1 = $this->makeProduct('TXC');
        $p2 = $this->makeProduct('TXD');

        // Snapshot every table the publish path can touch.
        $before = [
            'purchases'          => Purchase::count(),
            'purchase_items'     => PurchaseItem::count(),
            'stock_batches'      => StockBatch::count(),
            'supplier_payments'  => SupplierPayment::count(),
            'fund_transactions'  => FundTransaction::count(),
            'purchase_logs'      => PurchaseLog::count(),
        ];
        $p1StockBefore = (int) $p1->fresh()->stock;
        $p2StockBefore = (int) $p2->fresh()->stock;
        $dueBefore     = (float) $supplier->fresh()->current_due;

        // Force failure on the SECOND stockIn (i.e. item 2) — item 1 has
        // already created its batch, incremented stock, etc. by then.
        $real = app(StockManagementService::class);
        $call = 0;
        $mock = \Mockery::mock(StockManagementService::class)->makePartial();
        $mock->shouldReceive('stockIn')->andReturnUsing(
            function ($product, array $data) use (&$call, $real) {
                $call++;
                if ($call === 2) {
                    throw new \RuntimeException('Simulated mid-loop stock failure');
                }
                return $real->stockIn($product, $data);
            }
        );
        $this->instance(StockManagementService::class, $mock);

        $response = $this->post(route('purchases.store'), $this->validPayload($supplier, [
            ['product_id' => $p1->id, 'qty' => 5, 'unit_cost' => 40],
            ['product_id' => $p2->id, 'qty' => 3, 'unit_cost' => 60],
        ]));

        // The exception was caught (not a 500), user sees an error.
        $response->assertStatus(302);
        $response->assertSessionHasErrors('error');

        // NOTHING was persisted — no partial purchase.
        $this->assertSame($before['purchases'], Purchase::count());
        $this->assertSame($before['purchase_items'], PurchaseItem::count());
        $this->assertSame($before['stock_batches'], StockBatch::count());
        $this->assertSame($before['supplier_payments'], SupplierPayment::count());
        $this->assertSame($before['fund_transactions'], FundTransaction::count());
        $this->assertSame($before['purchase_logs'], PurchaseLog::count());

        // Stock, supplier due untouched.
        $this->assertSame($p1StockBefore, (int) $p1->fresh()->stock);
        $this->assertSame($p2StockBefore, (int) $p2->fresh()->stock);
        $this->assertSame($dueBefore, (float) $supplier->fresh()->current_due);
    }
}
