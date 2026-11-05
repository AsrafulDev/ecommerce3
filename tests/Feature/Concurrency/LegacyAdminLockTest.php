<?php

namespace Tests\Feature\Concurrency;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\StockBatch;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * The admin cancel runs in a separate PHP process, with its own database connection, while this test holds the order
 * lock in its own transaction. The cancel must really wait on that lock (InnoDB LOCK WAIT), then read the order as the
 * committed change left it, and refuse. A cancel that read the order before the lock would cancel a delivered sale
 * and restock it.
 *
 * Same harness as ConcurrentSalesResetTest: committed fixtures (DatabaseTruncation) and a worker process per action.
 */
class LegacyAdminLockTest extends TestCase
{
    use DatabaseTruncation;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.connections.mysql.database') !== 'ecommerce3_test') {
            $this->fail('Concurrency tests run only against ecommerce3_test.');
        }

        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $category = Category::create(['name' => 'Lock', 'slug' => 'lock-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Locked item', 'slug' => 'lock-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'LK-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
    }

    /** A committed order that has left stock (packed), so the cancel and the restock have something to act on. */
    protected function tearDown(): void
    {
        // These tests commit their fixtures, and DatabaseTruncation clears them only before the next such test.
        // Clear them now, so the RefreshDatabase tests that run after this one start from an empty database.
        $this->truncateTablesForAllConnections();
        parent::tearDown();
    }

    private function committedPackedOrder(): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-LK-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->buyerId(), 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Locked item', 'purchase_price' => 100,
        ]);
        app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::PACKED, 'Packed for the test.');

        return $order->fresh();
    }

    private function buyerId(): int
    {
        return Customer::firstOrCreate(['phone' => '01700000011'], [
            'name' => 'Lock buyer', 'slug' => 'lk-buyer', 'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;
    }

    private function spawn(array $job): Process
    {
        $config = config('database.connections.mysql');
        $env = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => $config['host'], 'DB_PORT' => (string) $config['port'],
            'DB_DATABASE' => 'ecommerce3_test', 'DB_USERNAME' => $config['username'], 'DB_PASSWORD' => (string) $config['password'],
            'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
        ];

        return new Process([PHP_BINARY, base_path('tests/Support/Concurrency/worker.php'), json_encode($job)], base_path(), $env, null, 120);
    }

    private function decode(Process $process): array
    {
        $out = json_decode($process->getOutput(), true);

        $this->assertIsArray($out, "worker produced no result. stderr: {$process->getErrorOutput()}");

        return $out;
    }

    /** Waits until a process is blocked on a row lock (InnoDB LOCK WAIT). Returns how many were seen. */
    private function waitForLockWaiter(int $seconds = 30): int
    {
        $deadline = microtime(true) + $seconds;
        do {
            $waiting = (int) DB::selectOne("select count(*) as c from information_schema.innodb_trx where trx_state = 'LOCK WAIT'")->c;
            if ($waiting > 0) {
                return $waiting;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);

        return 0;
    }

    private function stock(): int
    {
        return (int) StockBatch::where('product_id', $this->product->id)->sum('remaining_qty');
    }

    public function test_an_admin_cancel_waits_behind_an_in_flight_delivery_and_refuses_from_the_fresh_state(): void
    {
        $order = $this->committedPackedOrder();
        $this->assertSame(8, $this->stock());

        DB::beginTransaction();
        try {
            // A delivery is in flight and holds the order lock until this transaction ends.
            app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::DELIVERED, 'Delivery in flight.');

            $cancel = $this->spawn(['action' => 'admin-cancel', 'order_id' => $order->id]);
            $cancel->start();

            $this->assertGreaterThan(0, $this->waitForLockWaiter(), 'the cancel is really waiting on the order lock');
        } finally {
            DB::commit();
        }

        $cancel->wait();
        $out = $this->decode($cancel);

        $this->assertSame(422, $out['http'], 'the cancel is refused from the delivered state');
        $this->assertSame('delivered', $order->fresh()->order_status, 'the committed delivery was not overwritten');
        $this->assertSame(8, $this->stock(), 'a delivered sale is not restocked by a refused cancel');
        $this->assertSame(0, StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count());
    }
}
