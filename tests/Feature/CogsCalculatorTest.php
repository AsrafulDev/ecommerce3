<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use App\Services\CogsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * P4 — COGS stabilization. Every operational profit figure must come from the
 * ONE shared rule in App\Services\CogsCalculator:
 *
 *   cost  : stored realized order_details.cogs (a LINE TOTAL) first, then the
 *           line's own purchase_price snapshot × qty — never the live product
 *           price; and
 *   period: order_status ∈ {delivered, completed} + created_at window, so an
 *           unrelated later edit can never move COGS between periods.
 */
class CogsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected CogsCalculator $cogs;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cogs = app(CogsCalculator::class);

        $this->admin = User::create([
            'name'     => 'Owner',
            'email'    => 'cogs-owner@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $this->admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($this->admin, 'admin');
    }

    /* ---------------- helpers ---------------- */

    protected function makeProduct(float $purchasePrice = 100): Product
    {
        return Product::create([
            'name'           => 'COGS Product '.uniqid(),
            'slug'           => 'cogs-'.uniqid(),
            'category_id'    => 1,
            'product_code'   => 'COGS-'.uniqid(),
            'purchase_price' => $purchasePrice,
            'new_price'      => $purchasePrice * 2,
            'stock'          => 0,
            'status'         => 1,
        ]);
    }

    protected function makeCustomer(): Customer
    {
        return Customer::create([
            'name'     => 'COGS Customer',
            'slug'     => 'cogs-customer-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $orderAttrs  e.g. created_at / order_status overrides
     * @param  array<string, mixed>  $lineAttrs   e.g. cogs / purchase_price / qty overrides
     */
    protected function makeOrder(array $orderAttrs = [], array $lineAttrs = [], ?Product $product = null): Order
    {
        $product ??= $this->makeProduct();

        $order = Order::create(array_merge([
            'invoice_id'      => 'INV-COGS-'.uniqid(),
            'amount'          => 500,
            'discount'        => 0,
            'shipping_charge' => 0,
            'customer_id'     => $this->makeCustomer()->id,
            'order_status'    => OrderStatus::COMPLETED->value,
        ], $orderAttrs));

        OrderDetails::create(array_merge([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'purchase_price' => 100,
            'sale_price'     => 200,
            'qty'            => 2,
        ], $lineAttrs));

        return $order;
    }

    /* ---------------- 1. stored realized COGS is authoritative ---------------- */

    public function test_stored_realized_cogs_is_used_and_is_a_line_total_not_a_unit_cost(): void
    {
        // Realized total 300 for 2 units @ snapshot 100 (=200). The stored 300
        // must win, and must NOT be multiplied by qty again on the way in.
        $order = $this->makeOrder(lineAttrs: ['cogs' => 300]);

        $line = OrderDetails::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(300.0, $this->cogs->lineCogs($line));

        // Whole-order and period totals use the same single interpretation.
        $this->assertSame(300.0, $this->cogs->cogsForOrders([$order]));
        $this->assertSame(300.0, $this->cogs->periodProfit(Carbon::today()->subYear(), Carbon::now())['cogs']);
    }

    /* ---------------- 2. historical stability ---------------- */

    public function test_historical_cogs_is_stable_when_product_purchase_price_changes(): void
    {
        $product = $this->makeProduct(100);
        $order = $this->makeOrder(
            lineAttrs: ['cogs' => null, 'purchase_price' => 100, 'qty' => 3],
            product: $product
        );

        $before = $this->cogs->cogsForOrders($this->cogs->recognizedOrders());

        $product->update(['purchase_price' => 999]);

        $this->assertSame($before, $this->cogs->cogsForOrders($this->cogs->recognizedOrders()));
        $this->assertSame(300.0, $this->cogs->cogsForOrders($this->cogs->recognizedOrders()));
    }

    /* ---------------- 3. period boundaries ---------------- */

    public function test_period_window_includes_boundaries_and_excludes_outside(): void
    {
        $from = Carbon::create(2026, 3, 1)->startOfDay();
        $to   = Carbon::create(2026, 3, 31)->endOfDay();

        $this->makeOrder(['created_at' => $from->copy()], ['cogs' => 250]);                  // exactly from
        $this->makeOrder(['created_at' => $to->copy()], ['cogs' => 250]);                    // exactly to
        $this->makeOrder(['created_at' => $from->copy()->subSecond()], ['cogs' => 999]);     // one second before
        $this->makeOrder(['created_at' => $to->copy()->addSecond()], ['cogs' => 999]);       // one second after

        $this->assertCount(2, $this->cogs->recognizedOrders($from, $to));
        $this->assertSame(500.0, $this->cogs->cogsForOrders($this->cogs->recognizedOrders($from, $to)));
    }

    /* ---------------- 4. status filtering ---------------- */

    public function test_only_delivered_and_completed_orders_are_recognized(): void
    {
        foreach ([OrderStatus::PENDING, OrderStatus::SHIPPED, OrderStatus::CANCELLED, OrderStatus::RETURNED] as $status) {
            $this->makeOrder(['order_status' => $status->value]);
        }
        $this->makeOrder(['order_status' => OrderStatus::DELIVERED->value]);
        $this->makeOrder(['order_status' => OrderStatus::COMPLETED->value]);

        $this->assertCount(2, $this->cogs->recognizedOrders());
        $this->assertSame(400.0, $this->cogs->cogsForOrders($this->cogs->recognizedOrders()));
    }

    /* ---------------- 5. recognition-date proof (created_at, never updated_at) ---------------- */

    public function test_cogs_is_recognized_in_the_created_at_period_and_edits_cannot_move_it(): void
    {
        // Created last month, delivered only recently.
        $lastMonth = Carbon::now()->subMonth()->day(10);
        $order = $this->makeOrder(['created_at' => $lastMonth, 'order_status' => OrderStatus::DELIVERED->value], ['cogs' => 250]);

        $monthStart = Carbon::today()->startOfMonth();

        // This month: nothing (even though it was delivered/updated now).
        $this->assertCount(0, $this->cogs->recognizedOrders($monthStart, Carbon::now()));
        // Last month: it belongs there, created_at is the recognition basis.
        $this->assertCount(1, $this->cogs->recognizedOrders(
            $monthStart->copy()->subMonth(),
            $monthStart->copy()->subSecond()
        ));
        $this->assertSame(250.0, $this->cogs->cogsForOrders([$order]));

        // An unrelated edit today (moves updated_at) must not drag the order
        // into the current period.
        $order->touch();

        $this->assertCount(0, $this->cogs->recognizedOrders($monthStart, Carbon::now()));
        $this->assertSame(0.0, $this->cogs->cogsForOrders($this->cogs->recognizedOrders($monthStart, Carbon::now())));
    }

    /* ---------------- 6. AccountsController uses the shared rule ---------------- */

    public function test_accounts_dashboard_month_profit_equals_the_shared_rule(): void
    {
        $monthStart = Carbon::today()->startOfMonth();

        // In-month order: amount 500, realized cogs 300 → profit 200.
        $this->makeOrder(lineAttrs: ['cogs' => 300]);
        // Created last month but updated (touched) today — the old updated_at
        // rule wrongly counted this in the current month.
        $stale = $this->makeOrder(['created_at' => $monthStart->copy()->subMonth()], ['cogs' => 111]);
        $stale->touch();

        $this->get(route('admin.accounts.dashboard'))
            ->assertOk()
            ->assertViewHas('month_sales', 500.0)
            ->assertViewHas('month_profit', 200.0);
    }

    /* ---------------- 7. cross-controller same-COGS invariant ---------------- */

    public function test_profit_loss_report_and_accounts_dashboard_agree_on_cogs(): void
    {
        $monthStart = Carbon::today()->startOfMonth();

        $this->makeOrder(['amount' => 500], ['cogs' => 300]);
        $this->makeOrder(['amount' => 700], ['cogs' => null, 'purchase_price' => 120, 'qty' => 2]);

        $expected = $this->cogs->cogsForOrders($this->cogs->recognizedOrders($monthStart, Carbon::now()));
        $this->assertSame(540.0, $expected); // 300 realized + 120×2 snapshot

        $report = $this->get(route('admin.reports.profit_loss', ['type' => 'month']))->assertOk();
        $report->assertViewHas('cogs', $expected);

        $dashboard = $this->get(route('admin.accounts.dashboard'))->assertOk();
        $dashboard->assertViewHas('month_sales', 1200.0);
        // month_sales - month_profit must equal the very same COGS number.
        $implied = round(
            $dashboard->viewData('month_sales') - $dashboard->viewData('month_profit'),
            2
        );
        $this->assertSame($expected, $implied);
    }

    /* ---------------- 8. legacy fallback is explicit ---------------- */

    public function test_fallback_uses_the_lines_snapshot_and_never_the_live_product_price(): void
    {
        $product = $this->makeProduct(500);

        // cogs null → the snapshot on the LINE is used, not the product.
        $snapshot = $this->makeOrder(
            lineAttrs: ['cogs' => null, 'purchase_price' => 80, 'qty' => 4],
            product: $product
        );
        $line = OrderDetails::where('order_id', $snapshot->id)->first();
        $this->assertSame(320.0, $this->cogs->lineCogs($line));

        // No stored cost and no snapshot at all → 0, loudly a data bug at
        // source — the live product price must never silently rewrite history.
        $bare = $this->makeOrder(
            lineAttrs: ['cogs' => null, 'purchase_price' => 0, 'qty' => 3],
            product: $product
        );
        $bareLine = OrderDetails::where('order_id', $bare->id)->first();
        $this->assertSame(0.0, $this->cogs->lineCogs($bareLine));
    }

    /* ---------------- 9. decimal precision without float drift ---------------- */

    public function test_cogs_math_keeps_decimal_precision(): void
    {
        // Snapshot arithmetic: 12.35 × 3 is 37.049999… in raw IEEE floats; the
        // rule must land on 37.05 exactly. (The column today is int on this
        // schema, but live installs carry decimals — the math must be safe.)
        $row = new OrderDetails([
            'purchase_price' => 12.35,
            'qty'            => 3,
            'cogs'           => null,
        ]);
        $this->assertSame(37.05, $this->cogs->lineCogs($row));

        // Persisted decimal: 37.05 stored as the realized line total must come
        // back unchanged, and ten such lines sum to 370.50 — no drift.
        $order = $this->makeOrder(lineAttrs: ['cogs' => 37.05]);
        for ($i = 0; $i < 9; $i++) {
            OrderDetails::create([
                'order_id'       => $order->id,
                'product_id'     => 1,
                'product_name'   => 'x',
                'purchase_price' => 12,
                'sale_price'     => 20,
                'qty'            => 3,
                'cogs'           => 37.05,
            ]);
        }
        $this->assertSame(370.50, $this->cogs->cogsForOrders([$order]));
    }

    /* ---------------- 10. return / refund regression ---------------- */

    public function test_returned_order_leaves_the_recognized_set_and_a_refund_never_reverses_cogs_twice(): void
    {
        // Returned order: the engine NULLs cogs and the status leaves the
        // recognized pair — so its cost is out of the period exactly once
        // (no revenue, no COGS, and nothing to double-reverse).
        $returned = $this->makeOrder(['order_status' => OrderStatus::RETURNED->value], ['cogs' => null, 'purchase_price' => 0, 'qty' => 2]);
        $this->assertCount(0, $this->cogs->recognizedOrders());

        // A delivered order that was refunded in cash: the money leaves via
        // FundTransaction (contra revenue in the P&L); COGS recognition is
        // untouched and counted once.
        $refunded = $this->makeOrder(['amount' => 500, 'order_status' => OrderStatus::DELIVERED->value], ['cogs' => 300]);
        FundTransaction::create([
            'direction'  => 'out',
            'source'     => 'order_refund',
            'source_id'  => $refunded->id,
            'amount'     => 500,
            'created_by' => $this->admin->id,
        ]);

        $this->assertCount(1, $this->cogs->recognizedOrders());
        $this->assertSame(300.0, $this->cogs->cogsForOrders($this->cogs->recognizedOrders()));
        $this->assertSame(300.0, $this->cogs->cogsForOrders($this->cogs->recognizedOrders()));
    }
}
