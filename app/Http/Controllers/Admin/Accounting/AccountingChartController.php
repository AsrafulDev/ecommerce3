<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Softmit\DoubleEntry\Enums\AccountType;
use Softmit\DoubleEntry\Enums\NormalBalance;
use Softmit\DoubleEntry\Models\Account;

/**
 * Read-only chart of accounts, nested by parent and grouped by account type.
 * Editing accounts stays on the Accounts screen.
 */
class AccountingChartController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-list', ['only' => ['index']]);
        $this->middleware('permission:accounting-create', ['only' => ['store']]);
    }

    public function index()
    {
        $accounts = Account::query()->orderBy('code')->get(['id', 'code', 'name', 'account_type', 'normal_balance', 'parent_id', 'is_active']);

        $byParent = $accounts->groupBy(fn (Account $a) => $a->parent_id ?: 0);

        $groups = $accounts->whereNull('parent_id')
            ->groupBy(fn (Account $a) => $a->account_type?->value ?? 'other')
            ->map(fn ($roots) => $roots->map(fn (Account $a) => $this->node($a, $byParent))->values()->all())
            ->all();

        return view('backEnd.accounting.accounts.chart', [
            'groups' => $groups,
            'total' => $accounts->count(),
        ]);
    }

    /**
     * Add an account to the chart. Its opening balance is NOT set here: opening
     * balances go through the opening-balance worksheet so they are booked once,
     * with the checks that sheet makes.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('accounting_accounts', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'account_type' => ['required', Rule::in(array_column(AccountType::cases(), 'value'))],
            'normal_balance' => ['required', Rule::in(array_column(NormalBalance::cases(), 'value'))],
            'parent_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
        ]);

        Account::create($data + ['is_active' => true, 'is_system' => false]);

        return redirect()->route('admin.accounting.chart.index')
            ->with('success', "Account {$data['code']} added. Set its opening balance on the Opening Balances screen.");
    }

    /** @return array{account: Account, children: array<int, mixed>} */
    protected function node(Account $account, $byParent): array
    {
        return [
            'account' => $account,
            'children' => ($byParent->get($account->id) ?? collect())
                ->map(fn (Account $child) => $this->node($child, $byParent))->values()->all(),
        ];
    }
}
