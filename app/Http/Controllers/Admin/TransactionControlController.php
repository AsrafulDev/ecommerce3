<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FinancialTransactionPurgeLog;
use App\Models\FundTransaction;
use App\Services\Accounting\TransactionPurgeEligibilityService;
use App\Services\Accounting\FinancialTransactionPurgeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class TransactionControlController extends Controller
{
    public function __construct(private TransactionPurgeEligibilityService $eligibility, private FinancialTransactionPurgeService $purgeService)
    {
        $this->middleware('permission:purge-financial-transactions');
    }

    public function purge(Request $request, string $type, int $id)
    {
        $actor = auth('admin')->user();
        abort_unless($actor && ($actor->id === 1 || $actor->hasRole('admin')), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string'],
        ]);

        $expected = 'DELETE '.strtoupper(str_replace('_', '-', $type)).'-'.$id;
        if (!hash_equals($expected, trim($validated['confirmation']))) {
            return back()->withErrors(['confirmation' => 'Confirmation phrase does not match.']);
        }

        $key = 'transaction-purge:'.$actor->getAuthIdentifier().'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['password' => 'Too many purge attempts. Try again later.']);
        }
        RateLimiter::hit($key, 60);

        if (!Hash::check($validated['password'], $actor->password)) {
            return back()->withErrors(['password' => 'Authentication failed.']);
        }

        try {
            $this->purgeService->purge($type, $id, $actor, $validated['reason']);
            RateLimiter::clear($key);
            return back()->with('success', 'Transaction purged and recorded in the immutable audit history.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['purge' => $e instanceof \DomainException ? $e->getMessage() : 'Purge could not be completed. No changes were committed.']);
        }
    }

    public function index(Request $request)
    {
        $query = trim((string) $request->get('q'));
        $expenses = Expense::query()->latest('id');
        $funds = FundTransaction::query()->latest('id');

        if ($query !== '') {
            $expenses->where(fn ($q) => $q->where('title', 'like', "%{$query}%")
                ->orWhere('category', 'like', "%{$query}%")
                ->orWhere('id', $query));
            $funds->where(fn ($q) => $q->where('source', 'like', "%{$query}%")
                ->orWhere('note', 'like', "%{$query}%")
                ->orWhere('id', $query));
        }

        $rows = $expenses->limit(25)->get()->map(fn ($row) => $this->row('expense', $row))
            ->concat($funds->limit(25)->get()->map(fn ($row) => $this->row('fund_transaction', $row)))
            ->sortByDesc('effective_date')->values();

        return view('backEnd.transaction-control.index', [
            'rows' => $rows,
            'query' => $query,
            'logs' => FinancialTransactionPurgeLog::latest('id')->limit(25)->get(),
        ]);
    }

    private function row(string $type, object $record): array
    {
        $inspection = $this->eligibility->inspect($type, $record->id);
        $purgeType = $type === 'expense' ? 'expense' : match ($record->transaction_category?->value) {
            'other_income' => 'other_income', 'owner_capital' => 'owner_capital', 'owner_withdrawal' => 'owner_withdrawal', default => null,
        };
        if ($purgeType && $purgeType !== $type) {
            $specific = $this->eligibility->inspect('fund_transaction', $record->id);
            $inspection['eligible'] = $specific['eligible'];
            $inspection['blockers'] = $specific['blockers'];
            $inspection['dependencies'] = $specific['dependencies'];
        }
        return [
            'type' => $type,
            'id' => $record->id,
            'reference' => $type === 'expense' ? $record->title : strtoupper((string) $record->source),
            'amount' => $record->amount,
            'effective_date' => $inspection['effective_date'],
            'age_days' => $inspection['age_days'],
            'days_remaining' => $inspection['days_remaining'],
            'blockers' => $inspection['blockers'],
            'eligible' => $inspection['eligible'],
            'purge_type' => $purgeType,
            'expected_confirmation' => $purgeType ? 'DELETE '.strtoupper(str_replace('_', '-', $purgeType)).'-'.$record->id : null,
        ];
    }
}
