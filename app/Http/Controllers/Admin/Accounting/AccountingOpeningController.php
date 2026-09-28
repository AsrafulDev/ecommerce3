<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\OpeningBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Exceptions\AccountingException;
use Softmit\DoubleEntry\Models\Account;

/**
 * Opening balances: the one act where the books are created out of the old
 * records instead of out of business events.
 *
 * Deliberately two steps. The worksheet saves to a DRAFT journal that no report
 * reads, so an unfinished or wrong opening balance cannot mislead anybody; and
 * posting is a separate click that requires the operator to state they have
 * checked the figures, refuses an unbalanced worksheet, and can only ever happen
 * once. Nothing here computes a number and books it without showing the number,
 * the query it came from, and the reason it might be wrong.
 */
class AccountingOpeningController extends Controller
{
    public function __construct(protected OpeningBalanceService $opening)
    {
        $this->middleware('permission:accounting-list', ['only' => ['index']]);
        $this->middleware('permission:accounting-create', ['only' => ['save', 'balance', 'discard']]);
        $this->middleware('permission:accounting-edit', ['only' => ['post']]);
    }

    public function index()
    {
        if ($posted = $this->opening->posted()) {
            return view('backEnd.accounting.opening.locked', [
                'journal' => $posted->load('lines.account'),
            ]);
        }

        $derived = $this->opening->derive();
        $draft = $this->opening->draft();
        $lines = $draft ? $this->opening->editorLines($draft) : $derived['lines'];

        return view('backEnd.accounting.opening.index', [
            'accounts' => Account::active()->orderBy('code')->get(['id', 'code', 'name', 'normal_balance', 'role']),
            'parties'  => PartyType::cases(),
            'asAt'     => old('as_at', $draft?->transaction_date?->format('Y-m-d') ?? $this->opening->asAtDate()),
            'lines'    => array_merge($lines, array_fill(0, 5, $this->blankLine())),
            'totals'   => $this->opening->totals($lines),
            'plug'     => $this->opening->balancingLine($lines),
            'warnings' => $derived['warnings'],
            'missing'  => $derived['missing'],
            'draft'    => $draft,
        ]);
    }

    /**
     * Keep the worksheet as a draft — visible to the operator, invisible to every
     * report, and rewritable.
     */
    public function save(Request $request)
    {
        $request->validate($this->rules());

        try {
            $draft = $this->opening->saveDraft(
                $request->input('lines', []),
                $request->input('as_at'),
                $this->actor()
            );
        } catch (AccountingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounting.opening.index')
            ->with('success', "Opening balances kept as draft {$draft->journal_no}. Nothing is in the reports until you post it.");
    }

    /**
     * Add the line that would make the worksheet balance. Offered, never applied
     * on its own: the operator has to ask for it, and then still approve the whole
     * journal.
     */
    public function balance(Request $request)
    {
        $request->validate($this->rules());

        $lines = $request->input('lines', []);

        // The plug is measured on the rows as they stand, so the worksheet cannot
        // drift out of balance between this click and the save.
        $plug = $this->opening->balancingLine($lines);

        if ($plug === null) {
            return redirect()->route('admin.accounting.opening.index')
                ->with('info', 'The worksheet already balances — there is nothing to plug.');
        }

        try {
            $draft = $this->opening->saveDraft(
                array_merge($lines, [$plug]),
                $request->input('as_at'),
                $this->actor()
            );
        } catch (AccountingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounting.opening.index')
            ->with('info', "Added the balancing line. Check it is a fair statement of what the owner has in the business before you post — a plug is where mistakes go to hide.");
    }

    /**
     * Book the opening balances. After this the figures are history and can only
     * be corrected by reversing the journal.
     */
    public function post(Request $request)
    {
        $request->validate([
            'confirmed' => ['accepted'],
        ], [
            'confirmed.accepted' => 'Posting opening balances is the one thing that cannot be undone by editing. Confirm you have checked the figures against the till, the bank and the stock count.',
        ]);

        $draft = $this->opening->draft();

        if (!$draft) {
            return redirect()->route('admin.accounting.opening.index')
                ->with('error', 'There is no draft to post. Save the worksheet first.');
        }

        try {
            $journal = $this->opening->post($draft);
        } catch (AccountingException $e) {
            return redirect()->route('admin.accounting.opening.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.accounting.journals.show', $journal->id)
            ->with('success', "Opening balances posted as {$journal->journal_no}.");
    }

    /**
     * Throw the draft away. The worksheet then offers the derived figures again.
     */
    public function discard()
    {
        $draft = $this->opening->draft();

        $draft?->delete();

        return redirect()->route('admin.accounting.opening.index')
            ->with('info', 'Draft opening balance discarded. Only posted journals change the books.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $partyValues = array_map(fn (PartyType $case) => $case->value, PartyType::cases());

        return [
            'as_at'                => ['required', 'date'],
            'lines'                => ['present', 'array'],
            // Deliberately not `string`: a role name and an account id both arrive
            // here, and the service resolves either — an unresolvable one comes back
            // as a named, per-row error rather than a validation message that
            // contradicts what the select box offered.
            'lines.*.account_id'   => ['nullable', 'max:32'],
            'lines.*.description'  => ['nullable', 'string', 'max:255'],
            'lines.*.debit'        => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit'       => ['nullable', 'numeric', 'min:0'],
            'lines.*.party_type'   => ['nullable', Rule::in($partyValues)],
            'lines.*.party_id'     => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function blankLine(): array
    {
        return [
            'account_id' => '', 'description' => '', 'debit' => '', 'credit' => '',
            'party_type' => '', 'party_id' => '', 'source' => null,
        ];
    }

    protected function actor(): ?int
    {
        return Auth::guard('admin')->id() ? (int) Auth::guard('admin')->id() : null;
    }
}
