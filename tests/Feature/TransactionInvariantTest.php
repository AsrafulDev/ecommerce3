<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\StockBatch;
use App\Services\PaymentCollectionService;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-cutting money + stock invariants.
 *
 * Runs one connected flow through the real services (stock in → sale with a
 * partial payment → replayed payment → expense → refund → salary) and asserts
 * after every step that the independent ledgers still agree:
 *
 *   - fund balance      == Σ(in) − Σ(out)               (the single till)
 *   - order ledger      == Σ(order_payments) == fund 'customer_payment' in
 *   - order due         == order amount − Σ(order_payments)
 *   - product stock     == Σ(stock_batches.remaining_qty) (no drift)
 *   - replays are no-ops (no double credit, no double cash-in)
 */
class TransactionInvariantTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        config(['pricing.batch_wise' => false]);
    }

    private function fundIn(): float
    {
        return (float) FundTransaction::where('direction', 'in')->sum('amount');
    }

    private function fundOut(): float
    {
        return (float) FundTransaction::where('direction', 'out')->sum('amount');
    }

    private function assertFundBalanceMatchesLedger(): void
    {
        $this->assertEqualsWithDelta(
            $this->fundIn() - $this->fundOut(),
            FundHelper::balance(),
            0.01,
            'fund balance must equal Σ(in) − Σ(out)'
        );
    }

    private function assertStockMatchesBatches(): void
    {
        $batchSum = (int) StockBatch::where('product_id', $this->product->id)->sum('remaining_qty');
        $this->assertSame(
            $batchSum,
            (int) $this->product->fresh()->stock,
            'products.stock must equal Σ(stock_batches.remaining_qty)'
        );
    }

    public function test_full_money_and_stock_flow_stays_reconciled(): void
    {
        // ── 1. Stock in: 10 units @ 50 ───────────────────────────────────────
        $this->product = Product::create([
            'name'         => 'Invariant Product',
            'slug'         => 'invariant-' . uniqid(),
            'category_id'  => 1,
            'product_code' => 'INV-' . uniqid(),
            'purchase_price' => 50,
            'new_price'    => 100,
            'stock'        => 0,
            'status'       => 1,
        ]);
        app(StockManagementService::class)->stockIn($this->product, [
            'quantity'  => 10,
            'unit_cost' => 50,
        ]);
        $this->assertStockMatchesBatches();
        $this->assertSame(10, (int) $this->product->fresh()->stock);

        // ── 2. Sale: 4 units out, order total 400 ───────────────────────────
        $customer = Customer::create([
            'name'     => 'Invariant Customer',
            'slug'     => 'inv-cust-' . uniqid(),
            'phone'    => '017' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);
        $this->order = Order::create([
            'invoice_id'      => 'INV-TX-' . uniqid(),
            'amount'          => 400,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => \App\Enums\OrderStatus::PENDING->value,
        ]);
        app(StockManagementService::class)->stockOut($this->product, 4, [
            'reference_type' => 'order',
            'reference_id'   => $this->order->id,
        ]);
        $this->assertStockMatchesBatches();
        $this->assertSame(6, (int) $this->product->fresh()->stock);

        // ── 3. Partial customer payment 250 ──────────────────────────────────
        $service = app(PaymentCollectionService::class);
        $payment = $service->collect($this->order, 250, 'Cash', 'TRX-INV-1');
        $this->assertNotNull($payment);

        $this->order->refresh();
        $this->assertEqualsWithDelta(250, (float) $this->order->paid_amount, 0.01);
        $this->assertEqualsWithDelta(150, (float) $this->order->due_amount, 0.01);
        $this->assertEqualsWithDelta(
            (float) $this->order->amount - (float) OrderPayment::where('order_id', $this->order->id)->sum('amount'),
            (float) $this->order->due_amount,
            0.01,
            'order due must equal total − Σ(order_payments)'
        );
        $this->assertEqualsWithDelta(
            250,
            (float) FundTransaction::where('source', 'customer_payment')->sum('amount'),
            0.01,
            'customer payment must have exactly one cash-in fund row'
        );
        $this->assertFundBalanceMatchesLedger();

        // ── 4. Replays must not move money ──────────────────────────────────
        $inBefore = $this->fundIn();
        $service->collect($this->order, 250, 'Cash', 'TRX-INV-1');          // same gateway trx id
        FundHelper::creditSale($this->order);                                // sale top-up
        FundHelper::creditPayment($this->order, 250);                        // payment top-up
        $this->assertEqualsWithDelta($inBefore, $this->fundIn(), 0.01, 'replays must not add cash-in');
        $this->assertEqualsWithDelta(
            250,
            (float) OrderPayment::where('order_id', $this->order->id)->sum('amount'),
            0.01,
            'replayed collection must not create a second order payment'
        );

        // ── 5. Expense 30, refund 50, salary 100 ────────────────────────────
        FundTransaction::create(['direction' => 'out', 'source' => 'expense', 'source_id' => 1, 'amount' => 30, 'created_by' => 1]);
        FundHelper::debitRefund(1, 50, 'Refund for invariant order');
        FundHelper::debitRefund(1, 50, 'Refund replay');                     // idempotent
        FundTransaction::create(['direction' => 'out', 'source' => 'employee_salary', 'source_id' => 1, 'amount' => 100, 'created_by' => 1]);

        $this->assertEqualsWithDelta(
            250 - 30 - 50 - 100,
            FundHelper::balance(),
            0.01,
            'balance after expense, refund (once) and salary'
        );
        $this->assertEquals(1, FundTransaction::where('source', 'refund')->where('source_id', 1)->count());
        $this->assertFundBalanceMatchesLedger();
        $this->assertStockMatchesBatches();
    }
}
