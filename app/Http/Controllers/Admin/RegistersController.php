<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\Loan;
use App\Services\Registers\FixedAssetService;
use App\Services\Registers\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Loan and fixed asset registers: the entry and record screens. The rules and the
 * cash movements live in LoanService and FixedAssetService.
 */
class RegistersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:fund-list', ['only' => ['loans', 'assets']]);
        $this->middleware('permission:fund-create', ['only' => ['storeLoan', 'repayLoan', 'storeAsset', 'depreciateAsset', 'revalueAsset', 'disposeAsset']]);
    }

    public function loans(Request $request)
    {
        $loans = Loan::with('employee:id,name')
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->input('kind')))
            ->orderByDesc('loan_date')->orderByDesc('id')
            ->get();

        return view('backEnd.accounts.registers.loans', [
            'loans' => $loans,
            'employees' => Employee::orderBy('name')->get(['id', 'name']),
            'kind' => (string) $request->input('kind', ''),
        ]);
    }

    public function storeLoan(Request $request, LoanService $loans)
    {
        $data = $request->validate([
            'kind' => ['required', 'in:'.Loan::KIND_GENERAL.','.Loan::KIND_EMPLOYEE.','.Loan::KIND_EXPENSE_ADVANCE.','.Loan::KIND_SALARY_ADVANCE],
            'direction' => ['required', 'in:'.Loan::TAKEN.','.Loan::GIVEN],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', 'required_if:kind,'.Loan::KIND_EMPLOYEE.','.Loan::KIND_EXPENSE_ADVANCE.','.Loan::KIND_SALARY_ADVANCE],
            'party_name' => ['required_unless:kind,'.Loan::KIND_EMPLOYEE.','.Loan::KIND_EXPENSE_ADVANCE.','.Loan::KIND_SALARY_ADVANCE, 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'loan_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // An expense advance is cash handed to an employee, so it is always lent.
        if (in_array($data['kind'], [Loan::KIND_EXPENSE_ADVANCE, Loan::KIND_SALARY_ADVANCE], true) && $data['direction'] !== Loan::GIVEN) {
            return back()->withInput()->with('error', 'An advance is always money given to the employee.');
        }

        if (in_array($data['kind'], [Loan::KIND_EMPLOYEE, Loan::KIND_EXPENSE_ADVANCE, Loan::KIND_SALARY_ADVANCE], true)) {
            $data['party_name'] = Employee::find($data['employee_id'])->name;
        }

        try {
            $loan = $loans->open($data, Auth::id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        log_activity('accounts', 'create', "Loan {$loan->code} ".$loan->direction.' '.number_format((float) $loan->amount, 2).' with '.$loan->party_name, null, ['loan_id' => $loan->id]);

        return redirect()->route('admin.accounts.loans.index')->with('success', "Loan {$loan->code} recorded.");
    }

    public function repayLoan(Request $request, Loan $loan, LoanService $loans)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $repayment = $loans->repay($loan, (float) $data['amount'], $data['paid_on'], $data['note'] ?? null, Auth::id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        log_activity('accounts', 'create', "Repayment of ".number_format((float) $repayment->amount, 2)." on loan {$loan->code}", null, ['loan_id' => $loan->id]);

        return redirect()->route('admin.accounts.loans.index')->with('success', 'Repayment of '.number_format((float) $repayment->amount, 2).' recorded.');
    }

    public function assets()
    {
        return view('backEnd.accounts.registers.assets', [
            'assets' => FixedAsset::with('events')->orderByDesc('purchase_date')->orderByDesc('id')->get(),
        ]);
    }

    public function storeAsset(Request $request, FixedAssetService $assets)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'purchase_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $asset = $assets->purchase($data, Auth::id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        log_activity('accounts', 'create', "Fixed asset bought: {$asset->name} for ".number_format((float) $asset->cost, 2), null, ['asset_id' => $asset->id]);

        return redirect()->route('admin.accounts.assets.index')->with('success', "{$asset->name} recorded.");
    }

    public function depreciateAsset(Request $request, FixedAsset $asset, FixedAssetService $assets)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'event_date' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:255']]);

        return $this->runAssetAction(fn () => $assets->depreciate($asset, (float) $data['amount'], $data['event_date'], $data['note'] ?? null, Auth::id()), 'Depreciation recorded.');
    }

    public function revalueAsset(Request $request, FixedAsset $asset, FixedAssetService $assets)
    {
        $data = $request->validate(['value' => ['required', 'numeric', 'min:0'], 'event_date' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:255']]);

        return $this->runAssetAction(fn () => $assets->revalue($asset, (float) $data['value'], $data['event_date'], $data['note'] ?? null, Auth::id()), 'Revaluation recorded.');
    }

    public function disposeAsset(Request $request, FixedAsset $asset, FixedAssetService $assets)
    {
        $data = $request->validate([
            'value' => ['required', 'numeric', 'min:0'],
            'event_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $gain = $assets->dispose($asset, (float) $data['value'], $data['event_date'], $data['reason'], Auth::id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $result = $gain >= 0 ? 'gain of '.number_format($gain, 2) : 'loss of '.number_format(abs($gain), 2);

        return redirect()->route('admin.accounts.assets.index')->with('success', "{$asset->name} disposed of with a {$result}.");
    }

    protected function runAssetAction(callable $action, string $message)
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounts.assets.index')->with('success', $message);
    }
}
