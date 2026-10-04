<?php

namespace Tests\Feature\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\FinancialTransactionPurgeLog;
use App\Services\Accounting\AdvancedAccountingGateway;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TransactionPurgePhase2ATest extends AccountingTestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = \App\Models\User::create([
            'name' => 'Purge Owner', 'email' => 'purge-owner@test.local',
            'password' => Hash::make('secret-password'), 'status' => 1,
        ]);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::firstOrCreate(['name' => 'purge-financial-transactions', 'guard_name' => 'admin']);
        $role->givePermissionTo('purge-financial-transactions');
        $this->admin->assignRole($role);
        $this->actingAs($this->admin, 'admin');
    }

    public function test_expense_purge_removes_lite_and_advanced_rows_and_keeps_audit(): void
    {
        $fund = FundTransaction::create(['direction' => 'out', 'source' => 'expense', 'amount' => 100, 'note' => 'Test expense', 'created_by' => $this->admin->id]);
        $expense = Expense::create(['title' => 'Duplicate expense', 'amount' => 100, 'expense_date' => now()->toDateString(), 'category' => 'rent', 'fund_transaction_id' => $fund->id, 'created_by' => $this->admin->id]);
        $fund->update(['source_id' => $expense->id]);
        app(AdvancedAccountingGateway::class)->recordExpense($expense);

        $response = $this->post(route('admin.transaction-control.purge', ['expense', $expense->id]), [
            'reason' => 'Duplicate expense entered twice',
            'password' => 'secret-password',
            'confirmation' => 'DELETE EXPENSE-'.$expense->id,
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertDatabaseMissing('fund_transactions', ['id' => $fund->id]);
        $this->assertDatabaseMissing('accounting_journal_entries', ['source_id' => $expense->id]);
        $this->assertDatabaseHas('financial_transaction_purge_logs', ['source_id' => $expense->id]);
    }

    public function test_wrong_password_and_confirmation_do_not_delete(): void
    {
        $fund = FundTransaction::create(['direction' => 'in', 'source' => 'manual_add', 'transaction_category' => 'owner_capital', 'amount' => 100, 'created_by' => $this->admin->id]);

        $this->post(route('admin.transaction-control.purge', ['owner_capital', $fund->id]), [
            'reason' => 'Duplicate capital contribution', 'password' => 'wrong-password', 'confirmation' => 'DELETE OWNER-CAPITAL-'.$fund->id,
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('fund_transactions', ['id' => $fund->id]);
    }

    public function test_unsupported_type_is_not_routable_to_executable_purge(): void
    {
        $this->post('/admin/transaction-control/sale/1/purge', [
            'reason' => 'Unsupported transaction test', 'password' => 'secret-password', 'confirmation' => 'DELETE SALE-1',
        ])->assertNotFound();
    }

    public function test_lite_only_expense_purge_does_not_require_advanced_accounting(): void
    {
        config(['double-entry.enabled' => false]);
        $fund = FundTransaction::create(['direction' => 'out', 'source' => 'expense', 'amount' => 50, 'created_by' => $this->admin->id]);
        $expense = Expense::create(['title' => 'Lite-only expense', 'amount' => 50, 'expense_date' => now()->toDateString(), 'category' => 'misc', 'fund_transaction_id' => $fund->id, 'created_by' => $this->admin->id]);

        $this->post(route('admin.transaction-control.purge', ['expense', $expense->id]), [
            'reason' => 'Remove duplicate lite expense', 'password' => 'secret-password', 'confirmation' => 'DELETE EXPENSE-'.$expense->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertDatabaseMissing('fund_transactions', ['id' => $fund->id]);
    }
}
