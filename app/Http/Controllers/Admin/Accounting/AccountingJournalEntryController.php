<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Softmit\DoubleEntry\Exceptions\AccountingException;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Services\JournalPoster;
use Softmit\DoubleEntry\Support\JournalDraft;
use Softmit\DoubleEntry\Support\JournalLineDraft;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Support\Money;

/**
 * Manual journal entry: a balanced set of debit and credit lines posted as one
 * journal. Only reachable when the advanced accounting engine is on (the route
 * group carries the advanced-accounting middleware).
 */
class AccountingJournalEntryController extends Controller
{
    public function __construct(protected JournalPoster $poster)
    {
        $this->middleware('permission:accounting-list', ['only' => ['create']]);
        $this->middleware('permission:accounting-create', ['only' => ['store']]);
    }

    public function create()
    {
        return view('backEnd.accounting.journals.entry', [
            'accounts' => Account::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $draft = JournalDraft::make($data['transaction_date'])
            ->from(SourceType::MANUAL, null, 'Manual journal')
            ->key('manual:'.Str::uuid())
            ->about($data['description'])
            ->actor(Auth::id())
            ->approvedBy(Auth::id());

        $used = 0;
        foreach ($data['lines'] as $i => $line) {
            $debit = Money::of($line['debit'] ?? '0.00');
            $credit = Money::of($line['credit'] ?? '0.00');

            if (empty($line['account_id']) && Money::isZero($debit) && Money::isZero($credit)) {
                continue;
            }

            if (empty($line['account_id'])) {
                throw ValidationException::withMessages(["lines.{$i}.account_id" => 'Choose an account for this line.']);
            }
            if (!Money::isZero($debit) && !Money::isZero($credit)) {
                throw ValidationException::withMessages(["lines.{$i}.debit" => 'A line moves one way only: a debit or a credit, not both.']);
            }
            if (Money::isZero($debit) && Money::isZero($credit)) {
                throw ValidationException::withMessages(["lines.{$i}.debit" => 'Enter a debit or a credit amount for this line.']);
            }

            $draft->line(new JournalLineDraft((int) $line['account_id'], $debit, $credit, ($line['description'] ?? '') ?: $data['description'], null, null));
            $used++;
        }

        if ($used < 2) {
            return back()->withInput()->with('error', 'A journal needs at least two lines with amounts.');
        }

        try {
            $journal = $this->poster->post($draft);
        } catch (AccountingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounting.journals.show', $journal->id)
            ->with('success', "Journal {$journal->journal_no} posted.");
    }
}
