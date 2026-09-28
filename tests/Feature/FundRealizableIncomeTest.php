<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7.1 — one shared realizable-cash rule.
 *
 * Defect #1 was that the Accounts dashboard dropped a WHOLE 'sale' order unless
 * payment_status == 'paid', while FundHelper::balance() used a partial LEAST()
 * rule — so a partially-paid COD order was cash in one view and not even in the
 * other. Both now read the same FundHelper methods. This proves the partial rule
 * counts a collected portion, and that income() - spend() == balance().
 */
class FundRealizableIncomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
    }

    private function order(float $amount, string $paymentStatus, float $due): Order
    {
        $customer = Customer::create([
            'name'     => 'COD Customer',
            'slug'     => 'cod-' . uniqid(),
            'phone'    => '017' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);

        $order = Order::create([
            'invoice_id'      => 'INV-COD-' . uniqid(),
            'amount'          => $amount,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $customer->id,
            'order_status'    => \App\Enums\OrderStatus::COMPLETED->value,
        ]);

        // Simulate a delivered order whose sale was credited to the fund in full,
        // but only partially paid (COD with a courier-held remainder).
        FundHelper::creditSale($order, 'delivered', 1);
        $order->forceFill([
            'payment_status' => $paymentStatus,
            'paid_amount'    => $amount - $due,
            'due_amount'     => $due,
        ])->save();

        return $order;
    }

    public function test_partially_paid_cod_counts_only_the_collected_portion(): void
    {
        $this->order(1000, 'partial', 400); // 600 collected of 1000 credited

        // The old all-or-nothing rule would have excluded this order entirely (0).
        // The partial rule reports the 600 that is real cash.
        $this->assertEquals(600.0, FundHelper::income());
        $this->assertEquals(400.0, FundHelper::uncollectedSaleCredits());
        $this->assertEquals(600.0, FundHelper::balance()); // no spend yet
    }

    public function test_income_minus_spend_equals_balance(): void
    {
        $this->order(1000, 'partial', 400); // +600 realizable
        $this->order(500, 'paid', 0);       // +500 fully collected
        $this->order(200, 'pending', 200);  // delivered, nothing collected yet -> 0

        $this->assertEquals(1100.0, FundHelper::income());

        // A withdrawal is plain cash out.
        $tx = new \App\Models\FundTransaction();
        $tx->direction = 'out';
        $tx->source    = 'withdraw';
        $tx->amount    = 100;
        $tx->note      = 'owner took cash';
        $tx->created_by = 1;
        $tx->save();

        $this->assertEquals(100.0, FundHelper::spend());
        $this->assertEquals(
            1000.0,
            FundHelper::income() - FundHelper::spend(),
            'income() - spend() must equal balance() — one shared rule'
        );
        $this->assertEquals(FundHelper::income() - FundHelper::spend(), FundHelper::balance());
    }

    public function test_window_income_applies_the_same_rule(): void
    {
        $this->order(1000, 'partial', 400);

        $start = Carbon::today()->subMinute();
        $end   = Carbon::tomorrow();

        // Everything was booked today, so the day window sees the same realizable
        // cash, and a window that ends before the booking sees none.
        $this->assertEquals(600.0, FundHelper::income($start, $end));
        $this->assertEquals(0.0, FundHelper::income($start->copy()->subDay(), $start));
    }
}
