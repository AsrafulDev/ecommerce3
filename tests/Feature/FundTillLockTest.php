<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Expense;
use App\Models\FundTransaction;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The till is virtual, so balance-check-then-debit is serialised with a MySQL
 * named lock (FundHelper::withTillLock). These tests cover the refusal path and
 * the lock being released; true concurrency is not reproducible in one process.
 */
class FundTillLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(\App\Models\User::first(), 'admin');
    }

    public function test_till_lock_is_released_after_callback(): void
    {
        $result = FundHelper::withTillLock(fn () => 'ok');

        $this->assertSame('ok', $result);
        // Re-acquiring succeeds only if the first acquisition was released.
        $this->assertSame(1, (int) \Illuminate\Support\Facades\DB::selectOne('SELECT GET_LOCK(?, 0) AS got', ['fund_till'])->got);
        \Illuminate\Support\Facades\DB::select('SELECT RELEASE_LOCK(?)', ['fund_till']);
    }

    public function test_till_lock_is_released_when_callback_throws(): void
    {
        try {
            FundHelper::withTillLock(function () {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(1, (int) \Illuminate\Support\Facades\DB::selectOne('SELECT GET_LOCK(?, 0) AS got', ['fund_till'])->got);
        \Illuminate\Support\Facades\DB::select('SELECT RELEASE_LOCK(?)', ['fund_till']);
    }

    public function test_expense_above_balance_is_refused_and_writes_nothing(): void
    {
        FundTransaction::create([
            'direction' => 'in', 'source' => 'manual_add', 'amount' => 100, 'created_by' => 1,
        ]);

        $response = $this->post(route('admin.transactions.store'), [
            'date'     => now()->toDateString(),
            'amount'   => 500,
            'category' => 'rent',
            'note'     => 'Too big',
        ]);

        $response->assertSessionHas('error', 'Not enough balance in fund.');
        $this->assertSame(0, Expense::count());
        $this->assertSame(0, FundTransaction::where('direction', 'out')->count());
        $this->assertEqualsWithDelta(100.0, FundHelper::balance(), 0.01);
    }
}
