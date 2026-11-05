<?php

namespace Tests\Feature\Concurrency;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Courierapi;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\SmsGateway;
use App\Models\StockBatch;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Services\StockManagementService;
use App\Services\TransactionReset\SaleResetService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Real concurrency: the reset and the RedX webhook run in separate PHP processes, each with its own database connection
 * and transaction, against the test database. A test holds the order lock in its own transaction while the competing
 * process runs, and the test checks that the competitor is really waiting on the lock (InnoDB LOCK WAIT), then releases
 * it and checks what each side saw and the final stock and order state.
 *
 * The RedX parcel API is a local stand-in (tests/Support/Concurrency/redx-stub.php), so the webhook's verification
 * step is exercised over real HTTP.
 */
class ConcurrentSalesResetTest extends TestCase
{
    use DatabaseTruncation;

    private const STUB_PORT_RANGE = [21000, 29000];

    private Process $stub;

    private string $stubFile;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.connections.mysql.database') !== 'ecommerce3_test') {
            $this->fail('Concurrency tests run only against ecommerce3_test.');
        }

        config(['pricing.batch_wise' => false]);
        $this->seed(DefaultDatabaseSeeder::class);
        $this->admin = User::first();
        SmsGateway::query()->delete();
        \App\Models\GeneralSetting::query()->update(['vat_enabled' => false]);

        $this->stubFile = base_path('storage/framework/testing/redx-stub.json');
        @mkdir(dirname($this->stubFile), 0775, true);
        $this->startStub();

        Courierapi::create([
            'type' => 'redx', 'status' => 1, 'url' => $this->stubUrl(), 'token' => 'concurrency-token',
        ]);

        $category = Category::create(['name' => 'Conc', 'slug' => 'conc-'.uniqid(), 'status' => 1, 'parent_id' => 0]);
        $this->product = Product::create([
            'name' => 'Concurrent item', 'slug' => 'conc-item-'.uniqid(), 'category_id' => $category->id,
            'product_code' => 'CC-'.uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0, 'status' => 1,
            'costing_method' => 'fifo',
        ]);
        app(StockManagementService::class)->stockIn($this->product, ['quantity' => 10, 'unit_cost' => 100]);
    }

    protected function tearDown(): void
    {
        if (isset($this->stub) && $this->stub->isRunning()) {
            $this->stub->stop();
        }
        if (isset($this->stubFile) && is_file($this->stubFile)) {
            unlink($this->stubFile);
        }
        // These tests commit their fixtures, and DatabaseTruncation clears them only before the next such test.
        // Clear them now, so the RefreshDatabase tests that run after this one start from an empty database.
        $this->truncateTablesForAllConnections();
        parent::tearDown();
    }

    private function stubUrl(): string
    {
        return 'http://127.0.0.1:'.$this->stubPort;
    }

    private int $stubPort = 0;

    private function startStub(): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $port = random_int(self::STUB_PORT_RANGE[0], self::STUB_PORT_RANGE[1]);
            $this->stub = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", base_path('tests/Support/Concurrency/redx-stub.php')], base_path());
            $this->stub->start();
            $this->stubPort = $port;

            for ($wait = 0; $wait < 40; $wait++) {
                $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
                if ($socket) {
                    fclose($socket);

                    return;
                }
                usleep(50000);
            }

            $this->stub->stop();
        }

        $this->fail('The RedX stand-in server did not start.');
    }

    /** What RedX reports for each tracking number, read by the stand-in server. */
    private function redxSays(array $statusByTracking): void
    {
        file_put_contents($this->stubFile, json_encode($statusByTracking));
    }

    /** A committed packed order holding two units. Tracked when a tracking number is given. */
    private function committedPackedOrder(?string $tracking = null): Order
    {
        $order = Order::create([
            'invoice_id' => 'INV-CC-'.uniqid(), 'amount' => 400, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->buyerId(), 'order_status' => 'pending', 'created_at' => now(),
        ]);
        OrderDetails::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'sale_price' => 200, 'qty' => 2,
            'product_name' => 'Concurrent item', 'purchase_price' => 100,
        ]);
        app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::PACKED, 'Packed for the test.');

        if ($tracking !== null) {
            $order->update(['courier_type' => 'redx', 'courier_tracking_id' => $tracking, 'courier_sent_at' => now()]);
        }

        return $order->fresh();
    }

    private function buyerId(): int
    {
        return \App\Models\Customer::firstOrCreate(['phone' => '01700000009'], [
            'name' => 'Concurrent buyer', 'slug' => 'cc-buyer', 'password' => bcrypt('password'), 'verify' => 1, 'status' => 'active',
        ])->id;
    }

    /** @return array{action: string}|array<string, mixed> */
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
        return (int) $this->product->fresh()->stock;
    }

    private function restockBatches(Order $order): int
    {
        return StockBatch::where('reference_type', 'sale_return')->where('reference_id', $order->id)->count();
    }

    public function test_a_webhook_waits_behind_an_in_flight_reset_and_then_applies_from_the_fresh_state(): void
    {
        $order = $this->committedPackedOrder('RX-S1');
        $this->redxSays(['RX-S1' => 'returned']);

        DB::beginTransaction();
        try {
            // The reset holds the order lock. It is refused (a courier consignment exists), so nothing it did is kept,
            // but the lock stays with this transaction until it ends.
            $refusal = null;
            try {
                app(SaleResetService::class)->reverse($order->fresh(), $this->admin, 'Reset in flight.');
            } catch (\DomainException $e) {
                $refusal = $e->getMessage();
            }
            $this->assertStringContainsString('courier consignment', (string) $refusal);

            $webhook = $this->spawn(['action' => 'webhook', 'tracking' => 'RX-S1', 'payload_status' => 'returned']);
            $webhook->start();

            $this->assertGreaterThan(0, $this->waitForLockWaiter(), 'the webhook is really waiting on the order lock');
            $this->assertTrue($webhook->isRunning(), 'the webhook has not finished while the lock is held');
        } finally {
            DB::commit();
        }

        $webhook->wait();
        $out = $this->decode($webhook);

        $this->assertSame(200, $out['http']);
        $this->assertSame('applied', $out['body']['result'], 'the webhook applied the status from the fresh state');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, $this->stock(), 'restocked once');
        $this->assertSame(1, $this->restockBatches($order));
    }

    public function test_a_reset_waits_behind_an_in_flight_webhook_and_refuses_from_the_fresh_state(): void
    {
        $order = $this->committedPackedOrder('RX-S2');
        $this->redxSays(['RX-S2' => 'returned']);

        DB::beginTransaction();
        try {
            // The webhook's change is in flight: it holds the order lock until this transaction ends.
            app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::CANCELLED, 'Webhook change in flight.');

            $reset = $this->spawn(['action' => 'reset', 'order_id' => $order->id, 'admin_id' => $this->admin->id, 'reason' => 'Reset while webhook in flight.']);
            $reset->start();

            $this->assertGreaterThan(0, $this->waitForLockWaiter(), 'the reset is really waiting on the order lock');
            $this->assertTrue($reset->isRunning());
        } finally {
            DB::commit();
        }

        $reset->wait();
        $out = $this->decode($reset);

        $this->assertArrayHasKey('error', $out, 'the reset must not reverse a sale that is already cancelled');
        $this->assertStringContainsString('Already cancelled.', (string) ($out['message'] ?? ''));
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, $this->stock(), 'the webhook restocked once and the reset added nothing');
        $this->assertSame(1, $this->restockBatches($order));
    }

    public function test_two_simultaneous_deliveries_of_the_same_callback_apply_once(): void
    {
        $order = $this->committedPackedOrder('RX-S3');
        $this->redxSays(['RX-S3' => 'returned']);

        $first = $this->spawn(['action' => 'webhook', 'tracking' => 'RX-S3', 'payload_status' => 'returned']);
        $second = $this->spawn(['action' => 'webhook', 'tracking' => 'RX-S3', 'payload_status' => 'returned']);
        $first->start();
        $second->start();
        $first->wait();
        $second->wait();

        $results = [$this->decode($first)['body']['result'], $this->decode($second)['body']['result']];
        sort($results);

        $this->assertSame(['applied', 'unchanged'], $results, 'one delivery applied, the other was a no-op');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, $this->stock(), 'restocked once, not twice');
        $this->assertSame(1, $this->restockBatches($order));
    }

    public function test_a_late_callback_after_a_committed_reset_is_refused_and_changes_nothing(): void
    {
        $order = $this->committedPackedOrder(null);
        app(SaleResetService::class)->reverse($order->fresh(), $this->admin, 'Reset before the courier message.');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, $this->stock());

        // A courier message for this order arrives after the reset has committed.
        $order->update(['courier_type' => 'redx', 'courier_tracking_id' => 'RX-S4']);
        $this->redxSays(['RX-S4' => 'delivered']);

        $late = $this->spawn(['action' => 'webhook', 'tracking' => 'RX-S4', 'payload_status' => 'delivered']);
        $late->start();
        $late->wait();

        $out = $this->decode($late);
        $this->assertSame('refused', $out['body']['result']);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, $this->stock());
        $this->assertSame(0, FundTransaction::where('source', 'sale')->where('source_id', $order->id)->count(), 'no cash credited');
    }

    public function test_a_callback_waiting_behind_a_committed_change_re_reads_it_and_does_not_overwrite_it(): void
    {
        $order = $this->committedPackedOrder('RX-S6');
        $this->redxSays(['RX-S6' => 'returned']);

        DB::beginTransaction();
        try {
            // A delivery is in flight and holds the order lock. It is committed below.
            app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::DELIVERED, 'Delivery in flight.');

            $webhook = $this->spawn(['action' => 'webhook', 'tracking' => 'RX-S6', 'payload_status' => 'returned']);
            $webhook->start();

            $this->assertGreaterThan(0, $this->waitForLockWaiter(), 'the webhook is really waiting on the order lock');
        } finally {
            DB::commit();
        }

        $webhook->wait();
        $out = $this->decode($webhook);

        $this->assertSame('refused', $out['body']['result'], 'the webhook saw the delivery and refused the return');
        $this->assertSame('delivered', $order->fresh()->order_status, 'the committed delivery was not overwritten');
        $this->assertSame(8, $this->stock(), 'no restock was made for a sale that was delivered');
        $this->assertSame(0, $this->restockBatches($order));
    }

    public function test_a_callback_waiting_behind_a_rolled_back_change_sees_the_original_state(): void
    {
        $order = $this->committedPackedOrder('RX-S5');
        $this->redxSays(['RX-S5' => 'returned']);

        DB::beginTransaction();
        try {
            // An uncommitted delivery holds the order lock. It is rolled back below, so it never happens.
            app(OrderStatusService::class)->changeStatusLocked($order->id, OrderStatus::DELIVERED, 'Uncommitted delivery.');

            $webhook = $this->spawn(['action' => 'webhook', 'tracking' => 'RX-S5', 'payload_status' => 'returned']);
            $webhook->start();

            $this->assertGreaterThan(0, $this->waitForLockWaiter(), 'the webhook is really waiting on the order lock');
        } finally {
            DB::rollBack();
        }

        $webhook->wait();
        $out = $this->decode($webhook);

        $this->assertSame('applied', $out['body']['result'], 'it saw the packed state that survived the rollback');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(10, $this->stock());
        $this->assertSame(1, $this->restockBatches($order));
    }
}
