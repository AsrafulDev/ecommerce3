<?php

namespace Tests\Feature\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Softmit\DoubleEntry\Database\Seeders\ChartOfAccountsSeeder;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Services\AccountRegistry;
use Tests\TestCase;

/**
 * Shared harness for accounting tests.
 *
 * The chart of accounts is installed exactly as production installs it, so the
 * tests exercise the real role -> account resolution path instead of a stub.
 * Cutover is pushed into the past because "today" is a valid business-event date
 * in these tests; the tests that care about the cutover rule set it explicitly.
 */
abstract class AccountingTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These suites test the Advanced module itself: switch it on exactly as
        // an operator would, so the container binds the live gateway.
        config(['double-entry.enabled' => true]);

        $this->seed(ChartOfAccountsSeeder::class);

        config(['double-entry.cutover_date' => '2000-01-01']);

        $this->registry()->flush();
    }

    protected function registry(): AccountRegistry
    {
        return app(AccountRegistry::class);
    }

    protected function accountId(string $role): int
    {
        return $this->registry()->idFor($role);
    }

    protected function account(string $role): Account
    {
        return Account::where('role', $role)->firstOrFail();
    }
}
