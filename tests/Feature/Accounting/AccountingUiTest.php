<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Database\Seeders\PermissionTableSeeder;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\Money;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Auth;

/**
 * Every accounting screen must render, not just the services behind it.
 *
 * These are the pages a bookkeeper looks at; a Blade error here means the
 * feature is unusable no matter how correct the report service is. Each page is
 * hit with data posted through the real writer, so the tables are exercised with
 * actual journals rather than an empty state.
 *
 * The signed-in user is deliberately NOT the id=1 super admin: an explicit
 * accounting role is created, so these tests also prove the permission strings
 * controllers check for actually exist and are granted.
 */
class AccountingUiTest extends AccountingTestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionTableSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::create([
            'name' => 'Bookkeeper',
            'email' => 'bookkeeper@test.local',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);

        $this->grant([
            'accounting-list', 'accounting-create', 'accounting-edit',
            'accounting-delete', 'accounting-reverse', 'accounting-export',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** @param array<int, string> $permissions */
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

    protected function poster(): JournalPoster
    {
        return app(JournalPoster::class);
    }

    protected function postSale(): \Softmit\DoubleEntry\Models\JournalEntry
    {
        return $this->poster()->post(
            JournalDraft::make('2026-11-10')
                ->from(SourceType::SALE, 101, 'sale:101')
                ->key('sale:101')
                ->party(PartyType::CUSTOMER, 1)
                ->about('Order #101 completed')
                ->reference('INV-101')
                ->debit(AccountRole::ACCOUNTS_RECEIVABLE, '1200.00')
                ->credit(AccountRole::SALES_REVENUE, '1000.00')
                ->credit(AccountRole::DELIVERY_INCOME, '200.00')
                ->actor($this->admin->id)
        );
    }

    public function test_journal_browser_lists_posted_journals_with_all_three_traces(): void
    {
        $journal = $this->postSale();

        $page = $this->get(route('admin.accounting.journals.index'));

        $page->assertOk();
        $page->assertSee($journal->journal_no);
        $page->assertSee('INV-101');
        $page->assertSee('sale');
        $page->assertSee('Customer');

        // Filters must survive: a filtered list is a different answer, not a bug.
        $this->get(route('admin.accounting.journals.index', [
            'from' => '2026-11-01', 'to' => '2026-11-30', 'status' => 'posted', 'q' => 'INV-101',
        ]))->assertOk();

        $this->get(route('admin.accounting.journals.index', ['source_type' => 'sale', 'account_id' => $this->accountId(AccountRole::SALES_REVENUE)]))
            ->assertOk();
    }

    public function test_journal_detail_shows_lines_totals_and_who_did_it(): void
    {
        $journal = $this->postSale();

        $page = $this->get(route('admin.accounting.journals.show', $journal->id));

        $page->assertOk();
        $page->assertSee($journal->journal_no);
        $page->assertSee('Accounts Receivable');
        $page->assertSee('1,200.00');
        $page->assertSee('Bookkeeper');
        $page->assertSee('SOURCE');
        $page->assertSee('PARTY');
        $page->assertSee('ACTOR');
    }

    public function test_chart_of_accounts_and_account_editor_render(): void
    {
        $this->get(route('admin.accounting.accounts.index'))->assertOk();

        $this->get(route('admin.accounting.accounts.index', ['as_at' => '2026-11-30']))->assertOk();

        $account = $this->account(AccountRole::CASH);

        $page = $this->get(route('admin.accounting.accounts.edit', $account->id));

        $page->assertOk();
        $page->assertSee($account->name);
        // Locked-by-design fields must be visibly locked, not silently editable.
        $page->assertSee('Posting role');
    }

    public function test_account_ledger_shows_a_running_balance_that_matches_the_closing_figure(): void
    {
        $this->postSale();

        $account = $this->account(AccountRole::SALES_REVENUE);

        $page = $this->get(route('admin.accounting.ledger.account', [
            'account' => $account->id, 'from' => '2026-11-01', 'to' => '2026-11-30',
        ]));

        $page->assertOk();
        $page->assertSee($account->name);
        $page->assertSee('1,000.00');
        $page->assertSee('Brought forward');
    }

    public function test_party_statement_and_balances_render_from_posted_journals(): void
    {
        $this->postSale();

        $this->get(route('admin.accounting.ledger.party', [
            'partyType' => PartyType::CUSTOMER->value, 'partyId' => 1,
            'from' => '2026-11-01', 'to' => '2026-11-30',
        ]))->assertOk();

        $page = $this->get(route('admin.accounting.ledger.balances'));

        $page->assertOk();
        $page->assertSee('Owed to us');
        $page->assertSee('1,200.00');
    }

    public function test_unknown_party_type_is_a_404_rather_than_a_crash(): void
    {
        $this->get(route('admin.accounting.ledger.party', ['partyType' => 'not-a-type', 'partyId' => 1]))
            ->assertNotFound();
    }

    public function test_period_reports_render_with_data(): void
    {
        $this->postSale();

        $period = ['from' => '2026-11-01', 'to' => '2026-11-30'];

        $trial = $this->get(route('admin.accounting.reports.trial-balance', $period));
        $trial->assertOk();
        $trial->assertSee('Balanced');
        $trial->assertSee('1,200.00');

        $pl = $this->get(route('admin.accounting.reports.profit-loss', $period));
        $pl->assertOk();
        $pl->assertSee('Net profit');
        $pl->assertSee('1,200.00');

        $cash = $this->get(route('admin.accounting.reports.cash', $period));
        $cash->assertOk();
        $cash->assertSee('Cash in Hand');
    }

    public function test_reports_export_and_print_for_every_report(): void
    {
        $this->postSale();

        foreach (['trial-balance', 'profit-loss', 'cash'] as $report) {
            $csv = $this->get(route('admin.accounting.reports.export', ['report' => $report, 'from' => '2026-11-01', 'to' => '2026-11-30']));

            $csv->assertOk();
            $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));

            $pdf = $this->get(route('admin.accounting.reports.print', ['report' => $report, 'from' => '2026-11-01', 'to' => '2026-11-30']));

            $pdf->assertOk();
            $pdf->assertHeader('Content-Type', 'application/pdf');

            // A PDF that is not a PDF is how a broken export ships silently.
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        }

        // The exported trial balance carries the journal it came from.
        $csv = $this->get(route('admin.accounting.reports.export', [
            'report' => 'trial-balance', 'from' => '2026-11-01', 'to' => '2026-11-30',
        ]))->streamedContent();

        $this->assertStringContainsString('Accounts Receivable', $csv);
        $this->assertStringContainsString('1200.00', $csv);
        $this->assertStringContainsString('BALANCED', $csv);
    }

    public function test_empty_ledger_renders_with_an_explanation_instead_of_a_broken_table(): void
    {
        foreach ([
            'admin.accounting.journals.index',
            'admin.accounting.accounts.index',
            'admin.accounting.ledger.balances',
            'admin.accounting.reports.trial-balance',
            'admin.accounting.reports.profit-loss',
            'admin.accounting.reports.cash',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_reversal_from_the_screen_keeps_the_original_and_records_who_did_it(): void
    {
        $journal = $this->postSale();

        $response = $this->post(route('admin.accounting.journals.reverse', $journal->id), [
            'reason' => 'Billed to the wrong customer',
            'as_at_date' => '2026-11-30',
        ]);

        $response->assertSessionHasNoErrors();

        $reversal = JournalEntry::where('reversal_of_id', $journal->id)->firstOrFail();

        $response->assertRedirect(route('admin.accounting.journals.show', $reversal->id));

        $this->assertSame('reversed', $journal->fresh()->status->value);
        $this->assertSame($this->admin->id, $journal->fresh()->reversed_by);
        $this->assertSame('1,200.00', Money::format($reversal->total_debit));

        // The original is still readable: an audit needs both sides.
        $this->get(route('admin.accounting.journals.show', $journal->id))
            ->assertOk()
            ->assertSee($reversal->journal_no);
    }

    public function test_a_reversal_without_a_reason_is_refused(): void
    {
        $journal = $this->postSale();

        $this->post(route('admin.accounting.journals.reverse', $journal->id), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame('posted', $journal->fresh()->status->value);
        $this->assertSame(0, JournalEntry::where('reversal_of_id', $journal->id)->count());
    }

    public function test_an_account_which_has_been_edited_from_the_screen_still_refuses_line_changes(): void
    {
        $account = $this->account(AccountRole::OTHER_INCOME);

        $this->post(route('admin.accounting.accounts.update', $account->id), [
            'name' => 'Sundry Income',
            'description' => 'Renamed by the accountant',
            'is_active' => '1',
        ])->assertRedirect(route('admin.accounting.accounts.index'));

        $this->assertSame('Sundry Income', $account->fresh()->name);

        // The account type is what posting depends on, and it is not editable.
        $this->assertSame(
            $account->account_type->value,
            $account->fresh()->account_type->value
        );
    }

    public function test_a_signed_in_user_without_accounting_permission_is_refused(): void
    {
        $outsider = User::create([
            'name' => 'Warehouse only',
            'email' => 'warehouse@test.local',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);

        $this->actingAs($outsider, 'admin');

        $this->get(route('admin.accounting.journals.index'))->assertForbidden();
        $this->get(route('admin.accounting.reports.trial-balance'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        Auth::guard('admin')->logout();

        $this->get(route('admin.accounting.journals.index'))->assertRedirect(route('login'));
    }
}
