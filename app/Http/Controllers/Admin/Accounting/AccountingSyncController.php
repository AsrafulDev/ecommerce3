<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ManualEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Softmit\DoubleEntry\Enums\JournalStatus;
use Softmit\DoubleEntry\Enums\SourceType;
use Softmit\DoubleEntry\Models\JournalEntry;
use Toastr;

/**
 * The bridge screen between the Lite money books and the double-entry books.
 *
 * Lite rows (expenses and the two manual fund screens) already post their own
 * journal the moment they are saved. This screen is the catch-up and repair path:
 * it lists the rows that are still owed a journal — because they predate the
 * bridge, because a posting failed, or because the entry was reversed and a
 * corrected one is due — and lets the operator push them all in one go. It posts
 * through the same ManualEntryService the money screens use, so a row synced here
 * is indistinguishable from one booked at its own screen.
 *
 * The reset is reversal-only and scoped to Lite source types. Posted journals are
 * immutable, so "start over" never deletes anything: it mirrors the Lite entries
 * out and re-opens the rows for a fresh sync, leaving the full audit trail.
 */
class AccountingSyncController extends Controller
{
    public function __construct(protected ManualEntryService $accounting)
    {
        $this->middleware('permission:accounting-list', ['only' => ['index']]);
        $this->middleware('permission:accounting-create', ['only' => ['sync']]);
        $this->middleware('permission:accounting-reverse', ['only' => ['reset']]);
    }

    public function index()
    {
        $pending = $this->accounting->pending();

        // Rows are only previewed a few at a time; the sync itself reads afresh,
        // so a long backlog never means a huge page — just a truncated preview.
        return view('backEnd.accounting.sync.index', [
            'pending'      => $pending,
            'moneyInRows'  => $pending['money_ins']->take(50),
            'truncated'    => $pending['expenses']->count() >= 1000
                || $pending['withdrawals']->count() >= 1000
                || $pending['money_ins']->count() >= 1000,
            'reopenCount'  => JournalEntry::query()
                ->whereIn('source_type', array_map(fn (SourceType $t) => $t->value, ManualEntryService::LITE_SOURCE_TYPES))
                ->where('status', JournalStatus::POSTED->value)
                ->whereNull('reversal_of_id')
                ->count(),
        ]);
    }

    public function sync(Request $request)
    {
        // A money-in row with no recorded nature must be classified on purpose:
        // the choice moves money between equity and profit, so it is never inferred.
        $validated = $request->validate([
            'money_in_nature' => ['nullable', 'in:owner_capital,other_income'],
        ]);

        $result = $this->accounting->syncAll($validated['money_in_nature'] ?? null);

        log_activity('accounting', 'create', 'Lite money rows synced to the books', null, $result);

        Toastr::success(sprintf(
            'Sync complete: %d journal(s) posted, %d already up to date or skipped, %d failed.',
            $result['posted'],
            $result['pre_cutover'],
            $result['failed']
        ));

        if ($result['needs_nature'] > 0) {
            Toastr::warning(sprintf(
                '%d money-in row(s) were left unposted because you did not say whether they are owner capital or income. Pick a nature above and sync again.',
                $result['needs_nature']
            ));
        }

        if ($result['failed'] > 0) {
            Toastr::warning('Some journals could not be posted. See Accounting → Posting failures for the reason.');
        }

        return redirect()->route('admin.accounting.sync.index');
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'confirm' => ['required', 'string'],
        ]);

        if (mb_strtoupper(trim($validated['confirm'])) !== 'RE-OPEN') {
            Toastr::error('Type RE-OPEN exactly to confirm. Nothing was changed.');

            return redirect()->route('admin.accounting.sync.index');
        }

        $result = $this->accounting->resetLiteJournals(
            'Reset from Lite Data Sync — Lite rows re-opened for re-sync',
            Auth::guard('admin')->id()
        );

        log_activity('accounting', 'reverse', 'Lite-origin journals re-opened for re-sync', null, $result);

        if ($result['reversed'] === 0 && $result['failed'] === 0) {
            Toastr::info('There were no Lite-synced journals to re-open — the books were already clear.');
        } else {
            Toastr::success(sprintf(
                '%d Lite journal(s) reversed. The originals stay in the books; the Lite rows are open for a fresh sync.',
                $result['reversed']
            ));
        }

        if ($result['failed'] > 0) {
            Toastr::warning(implode(' | ', array_slice($result['errors'], 0, 5)));
        }

        return redirect()->route('admin.accounting.sync.index');
    }
}
