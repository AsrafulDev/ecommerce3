<?php

namespace Tests\Feature\Accounting;

use App\Models\Customer;
use App\Models\User;
use App\Services\Accounting\OpeningBalanceService;
use Database\Seeders\PermissionTableSeeder;
use Illuminate\Support\Facades\DB;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\TrialBalanceReport;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\Money;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Opening balances: the number the books start with.
 *
 * These tests hold the service to two promises — it reports what the records
 * actually say (including when they say two contradictory things), and it refuses
 * to book anything the operator has not balanced and approved.
 */
class OpeningBalanceTest extends AccountingTestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionTableSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Not the id=1 super admin: an explicit role keeps the permission strings
        // the controller checks for under test.
        $this->admin = User::create([
            'name'     => 'Bookkeeper',
            'email'    => 'opening@test.local',
            'password' => bcrypt('secret'),
            'status'   => 1,
        ]);

        $this->grant(['accounting-list', 'accounting-create', 'accounting-edit', 'accounting-reverse']);
        $this->actingAs($this->admin, 'admin');

        // The worksheet works out the position as at the day before trading starts
        // in the books, so the fixtures below are all older than that.
        config(['double-entry.cutover_date' => '2026-10-01']);

        // Money screens are the source of the fund figures, so the tests write
        // them the way the screens do.
        DB::table('fund_transactions')->insert([
            ['direction' => 'in', 'source' => 'manual_add', 'amount' => 1000.00, 'created_by' => $this->admin->id, 'created_at' => '2026-09-01 09:00:00', 'updated_at' => now()],
            ['direction' => 'out', 'source' => 'withdraw', 'amount' => 300.00, 'created_by' => $this->admin->id, 'created_at' => '2026-09-02 09:00:00', 'updated_at' => now()],
        ]);
    }

    protected function grant(array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'admin']);
        $role->syncPermissions($permissions);

        if (!$this->admin->hasRole($role->name)) {
            $this->admin->assignRole($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin->unsetRelation('roles')->unsetRelation('role')->unsetRelation('permissions');
    }

    public function test_cash_is_money_actually_received_not_money_promised(): void
    {
        $order = $this->order(['due_amount' => 500.00, 'payment_status' => 'pending', 'order_status' => 'confirmed']);
        $this->fundIn('sale', $order, 500.00);

        $lines = $this->derive()['lines'];
        $cash = $this->lineFor($lines, AccountRole::CASH);

        $this->assertSame('700.00', $cash['debit'], 'only collected money is cash');
        $this->assertSame('0.00', $cash['credit']);

        $warning = implode(' ', $this->derive()['warnings']);
        $this->assertStringContainsString('1,200.00', $warning, 'the fund screen counts the uncollected money');
        $this->assertStringContainsString('700.00', $warning, 'and the worksheet says which figure it used');
    }

    public function test_a_paid_order_counts_as_cash_only_when_the_order_is_paid(): void
    {
        $paid = $this->order(['due_amount' => 0.00, 'payment_status' => 'paid', 'order_status' => 'completed']);
        $this->fundIn('sale', $paid, 500.00);

        $cash = $this->lineFor($this->derive()['lines'], AccountRole::CASH);

        $this->assertSame('1200.00', $cash['debit']);
    }

    public function test_a_cancelled_order_is_not_money_owed_to_us(): void
    {
        $customer = $this->customer('Nabila');

        $this->order(['customer_id' => $customer, 'due_amount' => 400.00, 'payment_status' => 'pending', 'order_status' => 'cancelled']);
        $this->order(['customer_id' => $customer, 'due_amount' => 250.00, 'payment_status' => 'partial', 'order_status' => 'shipped']);

        $lines = $this->derive()['lines'];
        $receivable = $this->lineFor($lines, AccountRole::ACCOUNTS_RECEIVABLE);

        $this->assertSame('250.00', $receivable['debit']);
        $this->assertCount(1, array_filter($lines, fn ($l) => (int) $l['account_id'] === (int) $receivable['account_id']), 'one customer is one line');
        $this->assertSame($customer, (int) $receivable['party_id']);

        $warning = implode(' ', $this->derive()['warnings']);
        $this->assertStringContainsString('400.00', $warning);
        $this->assertStringContainsString('cancelled', $warning);
    }

    public function test_receivables_and_payables_belong_to_a_party_so_their_statements_open_correctly(): void
    {
        $customer = $this->customer('Rina');
        $supplier = $this->supplier('Gramco');

        $this->order(['customer_id' => $customer, 'due_amount' => 900.00, 'payment_status' => 'pending', 'order_status' => 'confirmed']);
        DB::table('suppliers')->where('id', $supplier)->update(['current_due' => 200.00]);

        $lines = $this->derive()['lines'];

        $receivable = $this->lineFor($lines, AccountRole::ACCOUNTS_RECEIVABLE);
        $payable = $this->lineFor($lines, AccountRole::ACCOUNTS_PAYABLE);

        $this->assertSame(PartyType::CUSTOMER->value, $receivable['party_type']);
        $this->assertSame($customer, (int) $receivable['party_id']);
        $this->assertStringContainsString('Rina', $receivable['description']);

        $this->assertSame(PartyType::SUPPLIER->value, $payable['party_type']);
        $this->assertSame($supplier, (int) $payable['party_id']);
        $this->assertSame('200.00', $payable['credit']);
    }

    public function test_stock_is_valued_from_the_batches_that_hold_it(): void
    {
        DB::table('stock_batches')->insert([
            'product_id' => 1, 'quantity' => 10, 'remaining_qty' => 4, 'unit_cost' => 125.50,
            'total_cost' => 1255.00, 'type' => 'in', 'created_at' => '2026-09-01 09:00:00', 'updated_at' => now(),
        ]);

        $inventory = $this->lineFor($this->derive()['lines'], AccountRole::INVENTORY);

        $this->assertSame('502.00', $inventory['debit']);
    }

    public function test_a_missing_answer_is_named_rather_than_guessed_at(): void
    {
        $gaps = implode(' ', $this->derive()['missing']);

        $this->assertStringContainsString('employee advances', mb_strtolower($gaps));
        $this->assertStringContainsString('courier', mb_strtolower($gaps));
        $this->assertStringContainsString('bank', mb_strtolower($gaps));
    }

    public function test_the_worksheet_is_offered_unbalanced_and_balances_only_when_asked(): void
    {
        $service = app(OpeningBalanceService::class);
        $lines = $this->derive()['lines'];

        $this->assertNotEquals('0.00', $service->totals($lines)['difference']);

        $plug = $service->balancingLine($lines);

        $this->assertNotNull($plug);
        $this->assertSame($this->accountId(AccountRole::RETAINED_EARNINGS), (int) $plug['account_id']);
        $this->assertSame('0.00', $plug['debit']);
        $this->assertTrue(Money::isPositive($plug['credit']));
        $this->assertSame('0.00', $service->totals(array_merge($lines, [$plug]))['difference']);
    }

    public function test_saving_the_worksheet_creates_a_draft_that_no_report_reads(): void
    {
        $this->saveWorksheet($this->balancedWorksheet());

        $draft = app(OpeningBalanceService::class)->draft();

        $this->assertNotNull($draft);
        $this->assertSame(JournalStatus::DRAFT, $draft->status);
        $this->assertSame(SourceType::OPENING->value, $draft->source_type->value);
        $this->assertSame(OpeningBalanceService::POSTING_KEY, $draft->posting_key);

        $report = app(TrialBalanceReport::class)->build();
        $this->assertSame('0.00', $report['totals']['closing_debit'], 'a draft must not appear in a report');
    }

    public function test_an_unbalanced_worksheet_cannot_be_booked(): void
    {
        $this->saveWorksheet([
            $this->row(AccountRole::CASH, '500.00', '0.00', 'Cash found in the till'),
        ]);

        $this->post(route('admin.accounting.opening.post'), ['confirmed' => 1])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('accounting_journal_entries', [
            'posting_key' => OpeningBalanceService::POSTING_KEY,
            'status'      => JournalStatus::DRAFT->value,
        ]);

        $this->assertSame(0, JournalEntry::where('status', JournalStatus::POSTED)->count());
    }

    public function test_posting_requires_the_operator_to_state_the_figures_were_checked(): void
    {
        $this->saveWorksheet($this->balancedWorksheet());

        $this->post(route('admin.accounting.opening.post'), [])
            ->assertSessionHasErrors('confirmed');

        $this->assertSame(0, JournalEntry::where('status', JournalStatus::POSTED)->count());
    }

    public function test_approving_the_worksheet_books_one_balanced_journal_the_day_before_trading_starts(): void
    {
        $this->saveWorksheet($this->balancedWorksheet());
        $this->post(route('admin.accounting.opening.post'), ['confirmed' => 1])->assertSessionHas('success');

        $journal = JournalEntry::posted()->where('posting_key', OpeningBalanceService::POSTING_KEY)->firstOrFail();

        $this->assertSame('2026-09-30', $journal->transaction_date->format('Y-m-d'));
        $this->assertTrue($journal->isBalanced());
        $this->assertSame($this->admin->id, (int) $journal->approved_by);
        $this->assertSame($this->admin->id, (int) $journal->created_by);

        // October trading starts from these figures rather than counting them again.
        $report = app(TrialBalanceReport::class)->build('2026-10-01', '2026-10-31');
        $this->assertTrue($report['balanced']);
        $this->assertSame('0.00', $report['totals']['movement_debit'], 'an opening journal is not income');
        $this->assertSame('500.00', $report['totals']['opening_debit']);
        $this->assertSame('500.00', $report['totals']['opening_credit']);
        $this->assertSame('500.00', $report['totals']['closing_debit']);
    }

    public function test_opening_balances_can_be_booked_exactly_once(): void
    {
        $this->saveWorksheet($this->balancedWorksheet());
        $this->post(route('admin.accounting.opening.post'), ['confirmed' => 1])->assertSessionHas('success');

        // A second attempt — a double click, or a different operator in a second
        // tab — must not book a second set of balances.
        $this->post(route('admin.accounting.opening.post'), ['confirmed' => 1]);

        $this->assertSame(1, JournalEntry::where('posting_key', OpeningBalanceService::POSTING_KEY)
            ->whereIn('status', [JournalStatus::POSTED, JournalStatus::REVERSED])
            ->count());

        $this->get(route('admin.accounting.opening.index'))
            ->assertOk()
            ->assertSee('not editable')
            ->assertDontSee('Save as draft');
    }

    public function test_a_worksheet_row_cannot_move_both_ways_or_go_below_zero(): void
    {
        $this->post(route('admin.accounting.opening.save'), [
            'as_at' => '2026-09-30',
            'lines' => [
                ['account_id' => $this->accountId(AccountRole::CASH), 'debit' => '500.00', 'credit' => '500.00', 'description' => 'Both sides'],
                ['account_id' => $this->accountId(AccountRole::OWNER_CAPITAL), 'debit' => '0.00', 'credit' => '500.00', 'description' => 'Equity'],
            ],
        ])->assertSessionHasErrors('lines.0.debit');

        $this->post(route('admin.accounting.opening.save'), [
            'as_at' => '2026-09-30',
            'lines' => [
                ['account_id' => $this->accountId(AccountRole::CASH), 'debit' => '-500.00', 'credit' => '0.00', 'description' => 'Negative'],
            ],
        ])->assertSessionHasErrors('lines.0.debit');

        $this->assertNull(app(OpeningBalanceService::class)->draft(), 'a refused worksheet leaves nothing half-saved');
    }

    public function test_a_row_without_an_account_is_refused_rather_than_dropped(): void
    {
        $this->post(route('admin.accounting.opening.save'), [
            'as_at' => '2026-09-30',
            'lines' => [
                ['account_id' => '', 'debit' => '500.00', 'credit' => '0.00', 'description' => 'Nowhere to go'],
            ],
        ])->assertSessionHasErrors('lines.0.account_id');

        $this->assertNull(app(OpeningBalanceService::class)->draft());
    }

    public function test_a_draft_is_replaced_rather_than_stacking_up_into_several_opening_balances(): void
    {
        $this->saveWorksheet([
            $this->row(AccountRole::CASH, '100.00', '0.00', 'Counted once'),
            $this->row(AccountRole::OWNER_CAPITAL, '0.00', '100.00', 'Owner'),
        ]);
        $this->saveWorksheet($this->balancedWorksheet());

        $this->assertSame(1, JournalEntry::where('posting_key', OpeningBalanceService::POSTING_KEY)->count());
        $this->assertSame('500.00', JournalEntry::where('posting_key', OpeningBalanceService::POSTING_KEY)->first()->total_debit);
    }

    public function test_reopening_the_worksheet_shows_the_saved_draft_not_the_original_derivation(): void
    {
        $this->saveWorksheet([
            $this->row(AccountRole::CASH, '1.00', '0.00', 'Counted in the till'),
            $this->row(AccountRole::OWNER_CAPITAL, '0.00', '1.00', 'Owner'),
        ]);

        $this->get(route('admin.accounting.opening.index'))
            ->assertOk()
            ->assertSee('Counted in the till')
            ->assertDontSee('Cash and funds on hand', false);
    }

    public function test_posting_the_worksheet_needs_permission_to_edit_the_books(): void
    {
        $this->saveWorksheet($this->balancedWorksheet());
        $this->grant(['accounting-list', 'accounting-create']);

        $this->post(route('admin.accounting.opening.post'), ['confirmed' => 1])->assertForbidden();

        $this->assertSame(0, JournalEntry::where('status', JournalStatus::POSTED)->count());
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function derive(): array
    {
        return app(OpeningBalanceService::class)->derive();
    }

    /** @return array<string, mixed> */
    private function lineFor(array $lines, string $role): array
    {
        $account = $this->accountId($role);

        foreach ($lines as $line) {
            if ((int) $line['account_id'] === $account) {
                return $line;
            }
        }

        $this->fail("no line was proposed for {$role}");
    }

    private function row(string $role, string $debit, string $credit, string $description): array
    {
        return [
            'account_id'  => $this->accountId($role),
            'description' => $description,
            'debit'       => $debit,
            'credit'      => $credit,
        ];
    }

    /** Cash in, owner capital back — the smallest worksheet that balances. */
    private function balancedWorksheet(): array
    {
        return [
            $this->row(AccountRole::CASH, '500.00', '0.00', 'Counted in the till'),
            $this->row(AccountRole::OWNER_CAPITAL, '0.00', '500.00', 'Owner equity carried forward'),
        ];
    }

    private function saveWorksheet(array $lines): void
    {
        $this->post(route('admin.accounting.opening.save'), ['as_at' => '2026-09-30', 'lines' => $lines])
            ->assertSessionHasNoErrors();
    }

    private function customer(string $name): int
    {
        return Customer::create([
            'name'     => $name,
            'slug'     => 'opening-'.uniqid(),
            'phone'    => '017'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'verify'   => 1,
            'status'   => 'active',
        ])->id;
    }

    private function supplier(string $name): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'name' => $name, 'current_due' => 0.00, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function order(array $attributes): int
    {
        return (int) DB::table('orders')->insertGetId(array_merge([
            'invoice_id'     => 'OB-'.uniqid(),
            'amount'         => 1000,
            'discount'       => 0,
            'shipping_charge' => 0,
            'paid_amount'    => 0.00,
            'due_amount'     => 0.00,
            'customer_id'    => 1,
            'order_status'   => 'pending',
            'payment_status' => 'pending',
            'order_type'     => 'online',
            'created_at'     => '2026-09-05 09:00:00',
            'updated_at'     => now(),
        ], $attributes));
    }

    private function fundIn(string $source, int $sourceId, float $amount): void
    {
        DB::table('fund_transactions')->insert([
            'direction' => 'in', 'source' => $source, 'source_id' => $sourceId,
            'amount' => $amount, 'created_by' => $this->admin->id,
            'created_at' => '2026-09-06 09:00:00', 'updated_at' => now(),
        ]);
    }
}
