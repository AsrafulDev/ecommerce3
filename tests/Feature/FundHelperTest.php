<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 (UPDATE-PLAN) — fund ledger credits are idempotent. FundHelper::
 * creditSale() creates ONE 'in' sale row per order with a balance snapshot, and
 * returns false when the order is already credited — so process page / webhook /
 * bulk update can never double-credit the ledger.
 */
class FundHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
    }

    protected function makeOrder(float $amount): Order
    {
        $customer = Customer::create([
            'name'     => 'Fund Customer',
            'slug'     => 'fund-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        return Order::create([
            'invoice_id'      => 'INV-FUND-'.uniqid(),
            'amount'          => $amount,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => \App\Enums\OrderStatus::COMPLETED->value,
        ]);
    }

    public function test_credit_sale_is_idempotent_and_snapshots_balance(): void
    {
        $order = $this->makeOrder(500);

        $balanceBefore = FundHelper::balance();

        // First credit creates the row…
        $created = FundHelper::creditSale($order, 'Order complete (test)', 1);
        $this->assertTrue($created);

        $row = FundTransaction::where('source', 'sale')->where('source_id', $order->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('in', $row->direction);
        $this->assertEquals(500.0, (float) $row->amount);
        $this->assertEquals($balanceBefore, (float) $row->balance_before);
        $this->assertEquals($balanceBefore + 500, (float) $row->balance_after);

        // …and a second credit is refused (no double-credit).
        $this->assertFalse(FundHelper::creditSale($order, 'Order complete (test again)', 1));
        $this->assertSame(1, FundTransaction::where('source', 'sale')->where('source_id', $order->id)->count());
    }
}
