<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\FixedAsset;
use App\Models\FundTransaction;
use App\Models\Loan;
use App\Models\User;
use App\Services\Registers\FixedAssetService;
use App\Services\Registers\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Loan and fixed asset registers: every cash movement is one fund row, refusals
 * leave the books untouched, and the balance sheet still balances.
 */
class RegistersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['double-entry.enabled' => false]);

        $this->admin = User::forceCreate(['id' => 2, 'name' => 'Owner', 'email' => 'owner@test.local', 'password' => bcrypt('secret'), 'status' => 1]);
        $this->admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $this->actingAs($this->admin, 'admin');
    }

    private function fundCash(float $amount): void
    {
        FundTransaction::create(['direction' => 'in', 'source' => 'manual_add', 'amount' => $amount, 'note' => 'opening cash', 'created_by' => $this->admin->id]);
    }

    private function loanData(string $direction, float $amount, string $party = 'Karim'): array
    {
        return ['kind' => Loan::KIND_GENERAL, 'direction' => $direction, 'party_name' => $party, 'amount' => $amount, 'loan_date' => now()->toDateString()];
    }

    public function test_borrowing_adds_cash_and_owing_is_outstanding(): void
    {
        app(LoanService::class)->open($this->loanData(Loan::TAKEN, 5000), $this->admin->id);

        $this->assertEquals(5000.00, FundHelper::balance());
        $loan = Loan::firstOrFail();
        $this->assertEquals(5000.00, $loan->outstanding());
        $this->assertSame('loan_taken', FundTransaction::where('source', 'loan')->firstOrFail()->transaction_category->value);
    }

    public function test_lending_more_than_the_fund_holds_is_refused(): void
    {
        $this->fundCash(1000);

        $this->expectException(RuntimeException::class);
        app(LoanService::class)->open($this->loanData(Loan::GIVEN, 2000), $this->admin->id);
    }

    public function test_repaying_more_than_is_outstanding_is_refused(): void
    {
        $this->fundCash(10000);
        $loan = app(LoanService::class)->open($this->loanData(Loan::TAKEN, 3000), $this->admin->id);

        try {
            app(LoanService::class)->repay($loan, 3500, now()->toDateString(), null, $this->admin->id);
            $this->fail('An over-repayment was accepted.');
        } catch (RuntimeException) {
        }

        $this->assertEquals(3000.00, $loan->fresh()->outstanding());
    }

    public function test_a_repayment_moves_cash_back_out_and_reduces_outstanding(): void
    {
        $this->fundCash(10000);
        $loan = app(LoanService::class)->open($this->loanData(Loan::TAKEN, 3000), $this->admin->id);

        app(LoanService::class)->repay($loan, 1000, now()->toDateString(), null, $this->admin->id);

        $this->assertEquals(2000.00, $loan->fresh()->outstanding());
        $this->assertEquals(12000.00, FundHelper::balance());
    }

    public function test_an_employee_loan_is_named_after_the_employee(): void
    {
        $this->fundCash(10000);
        $employeeId = \Illuminate\Support\Facades\DB::table('employees')->insertGetId([
            'name' => 'Rafiq Hasan', 'employee_id' => 'EMP-0001', 'email' => 'rafiq@test.local', 'joining_date' => now()->toDateString(), 'basic_salary' => 30000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post(route('admin.accounts.loans.store'), [
            'kind' => 'employee', 'direction' => 'given', 'employee_id' => $employeeId,
            'amount' => 1500, 'loan_date' => now()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame('Rafiq Hasan', Loan::where('kind', 'employee')->firstOrFail()->party_name);
        $this->get(route('admin.accounts.reports.show', 'employee-loan-balance'))->assertOk()->assertSee('Rafiq Hasan');
    }

    public function test_buying_an_asset_moves_cash_out_and_is_refused_above_the_fund(): void
    {
        $this->fundCash(5000);
        $assets = app(FixedAssetService::class);

        $assets->purchase(['name' => 'Delivery van', 'purchase_date' => now()->toDateString(), 'cost' => 3000], $this->admin->id);
        $this->assertEquals(2000.00, FundHelper::balance());

        $this->expectException(RuntimeException::class);
        $assets->purchase(['name' => 'Generator', 'purchase_date' => now()->toDateString(), 'cost' => 9000], $this->admin->id);
    }

    public function test_depreciation_reduces_book_value_without_moving_cash(): void
    {
        $this->fundCash(5000);
        $assets = app(FixedAssetService::class);
        $asset = $assets->purchase(['name' => 'Laptop', 'purchase_date' => now()->toDateString(), 'cost' => 1000], $this->admin->id);
        $before = FundHelper::balance();

        $assets->depreciate($asset, 250, now()->toDateString(), null, $this->admin->id);

        $this->assertEquals(750.00, $asset->fresh()->bookValue());
        $this->assertEquals($before, FundHelper::balance());
    }

    public function test_depreciating_more_than_the_book_value_is_refused(): void
    {
        $this->fundCash(5000);
        $assets = app(FixedAssetService::class);
        $asset = $assets->purchase(['name' => 'Laptop', 'purchase_date' => now()->toDateString(), 'cost' => 1000], $this->admin->id);

        $this->expectException(RuntimeException::class);
        $assets->depreciate($asset, 1200, now()->toDateString(), null, $this->admin->id);
    }

    public function test_disposal_returns_cash_and_records_the_gain(): void
    {
        $this->fundCash(5000);
        $assets = app(FixedAssetService::class);
        $asset = $assets->purchase(['name' => 'Printer', 'purchase_date' => now()->toDateString(), 'cost' => 1000], $this->admin->id);
        $assets->depreciate($asset, 400, now()->toDateString(), null, $this->admin->id);

        $gain = $assets->dispose($asset->fresh(), 700, now()->toDateString(), 'Sold', $this->admin->id);

        // Book value after 400 of depreciation is 600; selling for 700 is a gain of 100.
        $this->assertEquals(100.00, $gain);
        $this->assertEquals(4700.00, FundHelper::balance());
        $this->assertSame(FixedAsset::DISPOSED, $asset->fresh()->status);
    }

    public function test_a_disposed_asset_cannot_be_depreciated(): void
    {
        $this->fundCash(5000);
        $assets = app(FixedAssetService::class);
        $asset = $assets->purchase(['name' => 'Desk', 'purchase_date' => now()->toDateString(), 'cost' => 500], $this->admin->id);
        $assets->dispose($asset, 0, now()->toDateString(), 'Scrapped', $this->admin->id);

        $this->expectException(RuntimeException::class);
        $assets->depreciate($asset->fresh(), 10, now()->toDateString(), null, $this->admin->id);
    }

    public function test_the_balance_sheet_balances_once_loans_and_assets_are_recorded(): void
    {
        // The opening cash is owner capital, so it belongs on the capital side.
        FundTransaction::create([
            'direction' => 'in', 'source' => 'manual_add', 'amount' => 5000, 'note' => 'owner put in',
            'transaction_category' => \App\Enums\TransactionCategory::OWNER_CAPITAL, 'created_by' => $this->admin->id,
        ]);
        app(LoanService::class)->open($this->loanData(Loan::TAKEN, 2000), $this->admin->id);
        app(FixedAssetService::class)->purchase(['name' => 'Van', 'purchase_date' => now()->toDateString(), 'cost' => 3000], $this->admin->id);

        $this->get(route('admin.accounts.reports.show', 'balance-sheet'))
            ->assertOk()
            ->assertSee('Difference of 0.00');
    }

    public function test_loan_and_asset_reports_open(): void
    {
        $this->fundCash(5000);
        app(LoanService::class)->open($this->loanData(Loan::GIVEN, 500), $this->admin->id);
        app(FixedAssetService::class)->purchase(['name' => 'Van', 'purchase_date' => now()->toDateString(), 'cost' => 3000], $this->admin->id);

        foreach (['general-loan-balance', 'general-loan-ledger', 'employee-loan-ledger', 'fixed-asset-balance', 'fixed-asset-ledger'] as $slug) {
            $this->get(route('admin.accounts.reports.show', $slug))->assertOk();
        }
        $this->get(route('admin.accounts.reports.show', 'fixed-asset-balance'))->assertSee('Van');
    }
}
