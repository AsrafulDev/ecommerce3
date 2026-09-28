<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Softmit\DoubleEntry\Enums\PartyType;
use Softmit\DoubleEntry\Models\Account;
use Softmit\DoubleEntry\Services\ActorResolver;
use Softmit\DoubleEntry\Services\LedgerService;
use Softmit\DoubleEntry\Support\AccountRole;
use Softmit\DoubleEntry\Support\Money;

/**
 * Running-balance ledgers: an account's own book, and a counterparty's statement.
 */
class AccountingLedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-list');
    }

    public function account(Request $request, LedgerService $ledgers, Account $account)
    {
        $from = $request->input('from') ?: date('Y-m-01');
        $to = $request->input('to') ?: date('Y-m-d');

        return view('backEnd.accounting.ledger.account', [
            'ledger'  => $ledgers->forAccount($account, $from, $to, 50, (int) $request->input('page', 1)),
            'account' => $account,
            'from'    => $from,
            'to'      => $to,
            'actors'  => app(ActorResolver::class),
        ]);
    }

    public function party(Request $request, LedgerService $ledgers, string $partyType, int $partyId)
    {
        $type = PartyType::tryFrom($partyType);

        abort_if($type === null, 404, 'Unknown party type.');

        $from = $request->input('from') ?: null;
        $to = $request->input('to') ?: null;

        $party = $type->resolveModel($partyId);

        $statement = $ledgers->forParty($type, $partyId, $from, $to, null, null, 50, (int) $request->input('page', 1));

        return view('backEnd.accounting.ledger.party', [
            'statement' => $statement,
            'partyType' => $type,
            'partyId'   => $partyId,
            'party'     => $party,
            'from'      => $from,
            'to'        => $to,
            'actors'    => app(ActorResolver::class),
        ]);
    }

    /**
     * Who owes what: every receivable/payable subledger with an outstanding
     * balance, so the list itself is the action list.
     */
    public function balances(LedgerService $ledgers)
    {
        $roles = [
            AccountRole::ACCOUNTS_RECEIVABLE,
            AccountRole::ACCOUNTS_PAYABLE,
            AccountRole::EMPLOYEE_PAYABLE,
            AccountRole::EMPLOYEE_ADVANCE,
        ];

        $rows = [];

        foreach ($roles as $role) {
            if (!$this->registryHas($role)) {
                continue;
            }

            $account = Account::where('role', $role)->first();

            $parties = \DB::table('accounting_journal_lines')
                ->selectRaw('party_type, party_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->where('account_id', $account->id)
                ->whereNotNull('party_type')
                ->groupBy('party_type', 'party_id')
                ->get();

            foreach ($parties as $party) {
                $balance = $account->normal_balance->signedMovement(
                    Money::of($party->total_debit),
                    Money::of($party->total_credit)
                );

                if (Money::isZero($balance)) {
                    continue;
                }

                $type = PartyType::tryFrom($party->party_type);

                $rows[] = [
                    'account' => $account,
                    'role' => $role,
                    'party_type' => $type,
                    'party_id' => (int) $party->party_id,
                    'party_name' => $type ? ($type->resolveModel((int) $party->party_id)?->name ?? "Missing #{$party->party_id}") : $party->party_type,
                    'balance' => $balance,
                ];
            }
        }

        // Grouped by subledger first: "who owes us" and "who we owe" are opposite
        // questions and must never be ranked against each other.
        usort($rows, function ($a, $b) {
            return [$a['role'], Money::isNegative($b['balance'])] <=> [$b['role'], Money::isNegative($a['balance'])]
                ?: Money::compare($b['balance'], $a['balance']);
        });

        return view('backEnd.accounting.ledger.balances', [
            'rows' => $rows,
            'receivables' => Money::sum(array_map(fn ($r) => $r['role'] === AccountRole::ACCOUNTS_RECEIVABLE ? $r['balance'] : '0.00', $rows)),
            'payables' => Money::sum(array_map(fn ($r) => in_array($r['role'], [AccountRole::ACCOUNTS_PAYABLE, AccountRole::EMPLOYEE_PAYABLE], true) ? $r['balance'] : '0.00', $rows)),
        ]);
    }

    protected function registryHas(string $role): bool
    {
        return Account::where('role', $role)->where('is_active', true)->exists();
    }
}
