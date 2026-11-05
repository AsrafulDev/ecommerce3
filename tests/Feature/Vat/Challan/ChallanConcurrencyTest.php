<?php

namespace Tests\Feature\Vat\Challan;

use App\Models\VatChallan;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\Support\VatChallanFixtures;
use Tests\TestCase;

/**
 * Two challan issues run in separate PHP processes, each with its own database connection. The issue locks the sale
 * before it checks eligibility, and the number sequence is locked for the duration of the issue, so the outcome must
 * hold however the two interleave.
 *
 * Fixtures are committed, so the test clears them in tearDown. DatabaseTruncation clears them only before the next such test.
 */
class ChallanConcurrencyTest extends TestCase
{
    use DatabaseTruncation;
    use VatChallanFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.connections.mysql.database') !== 'ecommerce3_test') {
            $this->fail('Concurrency tests run only against ecommerce3_test.');
        }

        config(['pricing.batch_wise' => false]);
        $this->seed(\Database\Seeders\DefaultDatabaseSeeder::class);
        $this->seedSeller();
    }

    protected function tearDown(): void
    {
        // These tests commit their fixtures and their printed copies. Clear both, so later RefreshDatabase tests start clean.
        foreach (VatChallan::whereNotNull('pdf_path')->pluck('pdf_path') as $path) {
            Storage::disk('local')->delete($path);
        }
        $this->truncateTablesForAllConnections();
        parent::tearDown();
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

    /** Waits until at least $count processes are blocked on a row lock (InnoDB LOCK WAIT). Returns how many were seen. */
    private function waitForLockWaiters(int $count, int $seconds = 30): int
    {
        $deadline = microtime(true) + $seconds;
        do {
            $waiting = (int) DB::selectOne("select count(*) as c from information_schema.innodb_trx where trx_state = 'LOCK WAIT'")->c;
            if ($waiting >= $count) {
                return $waiting;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);

        return $waiting ?? 0;
    }

    public function test_two_issues_for_the_same_sale_produce_exactly_one_challan(): void
    {
        $order = $this->sale('INV-C-1', [[$this->challanProduct('Shirt'), 2, 400.00, 60.00, 15]]);

        // The test holds the sale's lock while both workers arrive, so both must be waiting on it when it is released.
        DB::beginTransaction();
        try {
            DB::table('orders')->where('id', $order->id)->lockForUpdate()->first();

            $first = $this->spawn(['action' => 'challan-issue', 'order_id' => $order->id]);
            $second = $this->spawn(['action' => 'challan-issue', 'order_id' => $order->id]);
            $first->start();
            $second->start();

            $this->assertSame(2, $this->waitForLockWaiters(2), 'both issues are really waiting on the sale lock');
        } finally {
            DB::commit();
        }

        $first->wait();
        $second->wait();
        $results = [$this->decode($first), $this->decode($second)];

        $issued = array_values(array_filter($results, fn ($r) => isset($r['issued'])));
        $refused = array_values(array_filter($results, fn ($r) => isset($r['refused'])));
        $this->assertCount(1, $issued, 'exactly one issue succeeds');
        $this->assertCount(1, $refused, 'the other is refused');
        $this->assertStringContainsString('already issued for this sale', implode(' ', $refused[0]['refused']));
        $this->assertSame(1, VatChallan::where('order_id', $order->id)->count());
    }

    public function test_issues_for_different_sales_at_the_same_time_take_distinct_sequential_numbers(): void
    {
        $orders = [
            $this->sale('INV-C-2', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]]),
            $this->sale('INV-C-3', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]]),
            $this->sale('INV-C-4', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]]),
        ];

        $processes = array_map(fn ($order) => $this->spawn(['action' => 'challan-issue', 'order_id' => $order->id]), $orders);
        foreach ($processes as $process) {
            $process->start();
        }
        foreach ($processes as $process) {
            $process->wait();
        }

        $numbers = [];
        foreach ($processes as $process) {
            $result = $this->decode($process);
            $this->assertArrayHasKey('issued', $result, implode(' ', $result['refused'] ?? [($result['error'] ?? 'no result').': '.($result['message'] ?? '')]));
            $numbers[] = $result['issued'];
        }

        $year = now()->year;
        sort($numbers);
        $this->assertSame(["CH-{$year}-000001", "CH-{$year}-000002", "CH-{$year}-000003"], $numbers, 'no number is taken twice and none is skipped');
        $this->assertSame(3, VatChallan::whereIn('order_id', array_map(fn ($o) => $o->id, $orders))->count());
    }
}
