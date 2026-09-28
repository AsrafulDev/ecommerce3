<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\PaymentCollectionService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7.2 — gateway cash-in and central collection.
 *
 * Defect #2 was that online gateway callbacks flipped payment_status to 'paid'
 * while leaving paid_amount and the fund ledger untouched, so real cash-in was
 * invisible to the Lite cash book and the order's totals disagreed with the
 * order_payments rows. Every collection now funnels through
 * PaymentCollectionService::collect(). These tests prove:
 *
 *   - a collection writes one OrderPayment row, keeps paid/due/status in step
 *     with that ledger, and creates a fund cash-in row,
 *   - a replayed webhook (same gateway trx id) is deduped to a single row,
 *   - an over-payment is capped to the outstanding due.
 */
class PaymentCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
    }

    private function order(float $amount): Order
    {
        $customer = Customer::create([
            'name'     => 'Gateway Customer',
            'slug'     => 'gw-' . uniqid(),
            'phone'    => '017' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        return Order::create([
            'invoice_id'      => 'INV-GW-' . uniqid(),
            'amount'          => $amount,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => \App\Enums\OrderStatus::PENDING->value,
        ]);
    }

    private function service(): PaymentCollectionService
    {
        return app(PaymentCollectionService::class);
    }

    public function test_a_full_collection_writes_ledger_and_fund_row(): void
    {
        $order = $this->order(1000);

        $payment = $this->service()->collect($order, 1000, 'bkash', 'TRX-FULL');

        $this->assertNotNull($payment);
        $this->assertEquals(1000.0, (float) $payment->amount);

        $order->refresh();
        $this->assertEquals(1000.0, (float) $order->paid_amount);
        $this->assertEquals(0.0, (float) $order->due_amount);
        $this->assertEquals('paid', $order->payment_status);

        // totals must equal the order_payments ledger exactly
        $this->assertEquals(
            (float) OrderPayment::where('order_id', $order->id)->sum('amount'),
            (float) $order->paid_amount
        );

        // the Lite cash book finally sees the money
        $this->assertDatabasehas('fund_transactions', [
            'source'    => 'sale',
            'source_id' => $order->id,
            'direction' => 'in',
        ]);
        $this->assertEquals(1000.0, FundHelper::creditedFor($order->id));
    }

    public function test_a_partial_collection_marks_the_order_partial(): void
    {
        $order = $this->order(1000);

        $this->service()->collect($order, 400, 'shurjopay', 'TRX-PART');

        $order->refresh();
        $this->assertEquals(400.0, (float) $order->paid_amount);
        $this->assertEquals(600.0, (float) $order->due_amount);
        $this->assertEquals('partial', $order->payment_status);
    }

    public function test_replayed_webhook_with_same_trx_is_deduped(): void
    {
        $order = $this->order(1000);

        $first  = $this->service()->collect($order, 1000, 'uddoktapay', 'TRX-REPLAY');
        $second = $this->service()->collect($order, 1000, 'uddoktapay', 'TRX-REPLAY');

        $this->assertNotNull($first);
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, OrderPayment::where('order_id', $order->id)->count());
        $this->assertEquals(1000.0, FundHelper::creditedFor($order->id));
    }

    public function test_a_second_collection_without_trx_is_capped_to_the_due(): void
    {
        $order = $this->order(1000);

        $this->service()->collect($order, 400, 'bkash', 'TRX-A');
        // No trx to dedup on, but the amount cap means we can never over-collect.
        $this->service()->collect($order, 900, 'bkash', 'TRX-B');

        $order->refresh();
        $this->assertEquals(1000.0, (float) $order->paid_amount);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(1000.0, FundHelper::creditedFor($order->id));

        // a further collection finds nothing left to take
        $nothing = $this->service()->collect($order, 500, 'bkash', 'TRX-C');
        $this->assertNull($nothing);
    }

    public function test_fund_cash_in_matches_the_collected_amount(): void
    {
        $order = $this->order(1000);

        $this->service()->collect($order, 600, 'aamarpay', 'TRX-AMT');

        $credited = (float) FundTransaction::where('source', 'sale')
            ->where('source_id', $order->id)
            ->where('direction', 'in')
            ->sum('amount');

        $this->assertEquals(600.0, $credited);
    }
}
