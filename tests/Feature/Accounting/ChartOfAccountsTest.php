<?php

namespace Tests\Feature\Accounting;

use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Database\Seeders\ChartOfAccountsSeeder;
use Softmit\DoubleEntry\Enums\AccountType;
use Softmit\DoubleEntry\Enums\NormalBalance;
use Softmit\DoubleEntry\Exceptions\UnknownAccountException;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Models\FundAccount;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;

/**
 * The chart of accounts is configuration, not constants: posting code asks for
 * roles, and a missing or disabled account must be a loud setup error.
 */
class ChartOfAccountsTest extends AccountingTestCase
{
    public function test_the_seed_creates_one_account_per_role_with_correct_normal_balance(): void
    {
        $missing = $this->registry()->missingRoles();

        $this->assertSame([], $missing, 'Every AccountRole must be backed by an active account.');

        $cash = $this->account(AccountRole::CASH);
        $this->assertSame(AccountType::ASSET, $cash->account_type);
        $this->assertSame(NormalBalance::DEBIT, $cash->normal_balance);

        $payable = $this->account(AccountRole::ACCOUNTS_PAYABLE);
        $this->assertSame(AccountType::LIABILITY, $payable->account_type);
        $this->assertSame(NormalBalance::CREDIT, $payable->normal_balance);
    }

    public function test_account_type_decides_behaviour_never_the_code_range(): void
    {
        $equity = $this->account(AccountRole::OWNER_CAPITAL);

        // Code 3000 sits in the "asset" range of many hand-rolled charts; the enum
        // is what matters, so renumbering the chart cannot change accounting.
        $this->assertSame('3000', $equity->code);
        $this->assertSame(AccountType::EQUITY, $equity->account_type);
        $this->assertFalse($equity->isProfitAndLoss());

        $this->assertTrue($this->account(AccountRole::SALES_REVENUE)->isProfitAndLoss());
        $this->assertTrue($this->account(AccountRole::COGS)->isProfitAndLoss());
    }

    public function test_sales_returns_is_a_revenue_account_with_a_debit_normal_balance(): void
    {
        $returns = $this->account(AccountRole::SALES_RETURNS);

        $this->assertSame(AccountType::REVENUE, $returns->account_type, 'Contra stays in the revenue class for reporting.');
        $this->assertSame(NormalBalance::DEBIT, $returns->normal_balance, 'But reduces revenue.');
    }

    public function test_seeding_twice_does_not_duplicate_or_repoint_accounts(): void
    {
        $before = Account::count();
        $rolesBefore = DB::table('accounting_accounts')->pluck('id', 'role')->all();

        $this->seed(ChartOfAccountsSeeder::class);

        $this->assertSame($before, Account::count());
        $this->assertSame($rolesBefore, DB::table('accounting_accounts')->pluck('id', 'role')->all());
        $this->assertSame(1, FundAccount::where('fund_key', 'default')->count());
    }

    public function test_renumbering_a_code_does_not_move_where_journals_land(): void
    {
        $cash = $this->account(AccountRole::CASH);
        $cash->update(['code' => '1999']);
        $this->registry()->flush();

        app(JournalPoster::class)->post(
            JournalDraft::make('2026-11-02')
                ->key('sale:code-test')
                ->debit(AccountRole::CASH, '100.00')
                ->credit(AccountRole::SALES_REVENUE, '100.00')
        );

        $posted = $this->account(AccountRole::CASH)->lines()->first();
        $this->assertSame($cash->id, $posted->account_id, 'Role resolution must follow the account, not the code.');
        $this->assertSame('1999', $posted->account->code);
    }

    public function test_an_unassigned_role_fails_loudly_instead_of_guessing_an_account(): void
    {
        Account::where('role', AccountRole::ACCOUNTS_PAYABLE)->update(['role' => null]);
        $this->registry()->flush();

        $this->assertContains(AccountRole::ACCOUNTS_PAYABLE, $this->registry()->missingRoles());

        try {
            $this->registry()->idFor(AccountRole::ACCOUNTS_PAYABLE);
            $this->fail('Expected UnknownAccountException.');
        } catch (UnknownAccountException $e) {
            $this->assertStringContainsString('accounts_payable', $e->getMessage());
        }
    }

    public function test_an_inactive_account_cannot_be_posted_to(): void
    {
        Account::where('role', AccountRole::SALES_REVENUE)->update(['is_active' => false]);
        $this->registry()->flush();

        $this->expectException(UnknownAccountException::class);

        app(JournalPoster::class)->post(
            JournalDraft::make('2026-11-02')
                ->debit(AccountRole::CASH, '10.00')
                ->credit(AccountRole::SALES_REVENUE, '10.00')
        );
    }

    public function test_an_unknown_role_or_missing_id_is_rejected(): void
    {
        try {
            $this->registry()->resolve('definitely_not_a_role');
            $this->fail('Expected UnknownAccountException.');
        } catch (UnknownAccountException $e) {
            $this->assertStringContainsString('neither a known role key nor an account id', $e->getMessage());
        }

        $this->expectException(UnknownAccountException::class);
        $this->registry()->resolve(999999);
    }

    public function test_the_default_fund_maps_to_the_cash_account(): void
    {
        $fund = FundAccount::forFund();

        $this->assertNotNull($fund);
        $this->assertSame($this->accountId(AccountRole::CASH), $fund->account_id);
        $this->assertTrue($fund->is_reconciling);
    }
}
