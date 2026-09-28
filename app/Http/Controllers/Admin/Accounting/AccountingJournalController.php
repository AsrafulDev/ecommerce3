<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Exceptions\AccountingException;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Models\JournalEntry;
use Softmit\DoubleEntry\Services\ActorResolver;
use Softmit\DoubleEntry\Services\ReversalService;
use Softmit\DoubleEntry\Support\Money;
use Toastr;

/**
 * Journal browser: every accounting event, with all three traces visible.
 *
 * Read + reverse only. There is no create/edit form here on purpose: journals
 * written by hand are created through the manual-entry screen, and anything
 * POSTED is corrected by reversal rather than by editing (see the model guards).
 */
class AccountingJournalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:accounting-reverse', ['only' => ['reverse']]);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['from', 'to', 'status', 'source_type', 'party_type', 'account_id', 'q']);

        $journals = JournalEntry::query()
            ->with('lines.account')
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['source_type'] ?? null, fn ($q, $v) => $q->where('source_type', $v))
            ->when($filters['party_type'] ?? null, fn ($q, $v) => $q->where('party_type', $v))
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('journal_no', 'like', "%{$v}%")
                        ->orWhere('reference', 'like', "%{$v}%")
                        ->orWhere('description', 'like', "%{$v}%");
                });
            })
            ->when($filters['account_id'] ?? null, function ($q, $v) {
                $q->whereHas('lines', fn ($l) => $l->where('account_id', $v));
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $actors = app(ActorResolver::class);

        return view('backEnd.accounting.journals.index', [
            'journals'  => $journals,
            'filters'   => $filters,
            'accounts'  => Account::orderBy('code')->get(['id', 'code', 'name']),
            'statuses'  => JournalStatus::cases(),
            'sources'   => SourceType::cases(),
            'parties'   => PartyType::cases(),
            'actors'    => $actors,
            'totals'    => [
                'debit'  => Money::sum($journals->pluck('total_debit')->all()),
                'credit' => Money::sum($journals->pluck('total_credit')->all()),
            ],
        ]);
    }

    public function show($id)
    {
        $journal = JournalEntry::with([
            'lines.account',
            'reversals.lines.account',
            'reversedSource',
        ])->findOrFail($id);

        return view('backEnd.accounting.journals.show', [
            'journal' => $journal,
            'actors'  => app(ActorResolver::class),
        ]);
    }

    /**
     * Correct a posted journal by counter-posting it. The original stays.
     */
    public function reverse(Request $request, ReversalService $reversals, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'as_at_date' => ['nullable', 'date'],
        ], [
            'reason.required' => 'A reversal must say why. "Mistake" is not enough for an audit.',
        ]);

        $journal = JournalEntry::findOrFail($id);

        try {
            $reversal = $reversals->reverse(
                $journal,
                $validated['reason'],
                Auth::guard('admin')->id(),
                $validated['as_at_date'] ?? null
            );
        } catch (AccountingException $e) {
            Toastr::error($e->getMessage());

            return back();
        }

        log_activity(
            'accounting',
            'reverse',
            "Journal {$journal->journal_no} reversed as {$reversal->journal_no}: {$validated['reason']}",
            $journal,
            ['reversal_id' => $reversal->id, 'reason' => $validated['reason']]
        );

        Toastr::success("Posted {$reversal->journal_no} to reverse {$journal->journal_no}. The original entry stays in the books.");

        return redirect()->route('admin.accounting.journals.show', $reversal->id);
    }
}
