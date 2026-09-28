<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Services\AccountingDefaults;
use Softmit\DoubleEntry\Services\AccountRegistry;
use Softmit\DoubleEntry\Support\Money;
use Toastr;

/**
 * Chart of accounts maintenance.
 *
 * Deliberately narrow: name, description and active flag. account_type and role
 * decide where money posts, so changing them here would silently move future
 * journals — that is a setup/seeder operation, not a dashboard click.
 */
class AccountingAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-list', ['only' => ['index']]);
        $this->middleware('permission:accounting-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:accounting-create', ['only' => ['syncDefaults']]);
    }

    /**
     * One-click install/repair of the package defaults: the standard chart of
     * accounts (Assets, Liabilities, Equity, Revenue, Cost of Sales, Expenses)
     * plus the default fund → GL mapping. Idempotent — accounts are matched by
     * role, so running it again changes nothing.
     */
    public function syncDefaults(AccountingDefaults $defaults)
    {
        try {
            $result = $defaults->sync();
        } catch (\RuntimeException $e) {
            // Genuine chart conflict — the sync rolled back; nothing was forced.
            Toastr::error($e->getMessage());

            return redirect()->route('admin.accounting.accounts.index');
        }

        log_activity('accounting', 'create', 'Accounting defaults synced', null, $result);

        if ($result['created'] > 0) {
            Toastr::success(sprintf(
                'Defaults synced: %d account(s) created, %d verified. Fund mapping: %s.',
                $result['created'],
                $result['verified'],
                $result['fund_mapped'] ? 'ok' : 'missing'
            ));
        } else {
            Toastr::info('All ' . $result['verified'] . ' default accounts were already present — nothing changed.');
        }

        if ($result['missing_roles']) {
            Toastr::warning('Still missing an active account for: ' . implode(', ', $result['missing_roles']));
        }

        return redirect()->route('admin.accounting.accounts.index');
    }

    public function index(AccountRegistry $registry)
    {
        $asAt = request('as_at') ?: null;

        $accounts = Account::query()
            ->with('parent')
            ->orderBy('account_type')
            ->orderBy('code')
            ->get();

        $totals = ['debit' => Money::zero(), 'credit' => Money::zero()];

        $rows = $accounts->map(function (Account $account) use ($asAt, &$totals) {
            $lineTotals = $account->lineTotals($asAt);
            $balance = $account->normal_balance->signedMovement($lineTotals['debit'], $lineTotals['credit']);

            if ($account->normal_balance->value === 'debit') {
                $totals['debit'] = Money::add($totals['debit'], $balance);
            } else {
                $totals['credit'] = Money::add($totals['credit'], $balance);
            }

            return [
                'account' => $account,
                'debit' => $lineTotals['debit'],
                'credit' => $lineTotals['credit'],
                'balance' => $balance,
            ];
        });

        return view('backEnd.accounting.accounts.index', [
            'rows' => $rows,
            'totals' => $totals,
            'asAt' => $asAt,
            'missingRoles' => $registry->missingRoles(),
        ]);
    }

    public function edit(Account $account)
    {
        return view('backEnd.accounting.accounts.edit', [
            'account' => $account,
        ]);
    }

    public function update(Request $request, AccountRegistry $registry, Account $account)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $activate = $request->boolean('is_active');

        $account->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $activate,
        ]);

        // Posting resolves roles through the memoised registry; a deactivation
        // must be seen by the next request that posts.
        $registry->flush();

        log_activity('accounting', 'update', "Account {$account->code} ({$account->name}) updated", $account, [
            'is_active' => $activate,
        ]);

        Toastr::success('Account updated.');

        return redirect()->route('admin.accounting.accounts.index');
    }
}
