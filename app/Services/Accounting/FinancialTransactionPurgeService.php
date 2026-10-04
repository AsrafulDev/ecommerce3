<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FinancialTransactionPurgeLog;
use App\Models\FundTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;

class FinancialTransactionPurgeService
{
    private const SUPPORTED = ['expense', 'other_income', 'owner_capital', 'owner_withdrawal'];

    public function __construct(private TransactionPurgeEligibilityService $eligibility, private AdvancedAccountingGateway $accounting) {}

    public function purge(string $type, int $id, Authenticatable $actor, string $reason): FinancialTransactionPurgeLog
    {
        if (!in_array($type, self::SUPPORTED, true)) throw new \DomainException('Executable purge is not supported for this transaction type.');

        return DB::transaction(function () use ($type, $id, $actor, $reason) {
            $record = $this->lockSource($type, $id);
            if (!$record) throw new \DomainException('The transaction no longer exists.');

            if ($record instanceof FundTransaction) {
                $expectedCategory = match ($type) {
                    'other_income' => 'other_income',
                    'owner_capital' => 'owner_capital',
                    'owner_withdrawal' => 'owner_withdrawal',
                    default => null,
                };
                if (!$expectedCategory || $record->source !== ($type === 'owner_withdrawal' ? 'withdraw' : 'manual_add') || $record->transaction_category?->value !== $expectedCategory) {
                    throw new \DomainException('The transaction type does not match its recorded source and category.');
                }
            }

            $inspection = $this->eligibility->inspect($this->resolverType($type), $id);
            if (!$inspection['eligible']) throw new \DomainException('Purge blocked: '.implode(', ', $inspection['blockers']));

            $before = $this->snapshot($record);
            $fundBefore = $record instanceof Expense && $record->fund_transaction_id
                ? $this->snapshot(FundTransaction::whereKey($record->fund_transaction_id)->lockForUpdate()->first())
                : null;
            $journal = $this->accounting->purgeFinancialJournal($this->journalType($type), $id);

            if ($record instanceof Expense) {
                if ($record->fund_transaction_id && FundTransaction::whereKey($record->fund_transaction_id)->lockForUpdate()->exists()) {
                    FundTransaction::whereKey($record->fund_transaction_id)->delete();
                }
                $record->delete();
            } else {
                $record->delete();
            }

            if ($this->sourceExists($type, $id)) throw new \RuntimeException('Source transaction still exists after purge.');
            if ($record instanceof Expense && $record->fund_transaction_id && FundTransaction::whereKey($record->fund_transaction_id)->exists()) {
                throw new \RuntimeException('Linked fund transaction still exists after purge.');
            }
            if ($journal && $this->accounting->financialJournalExists($this->journalType($type), $id)) {
                throw new \RuntimeException('Accounting journal still exists after purge.');
            }

            return FinancialTransactionPurgeLog::create([
                'transaction_type' => $type,
                'transaction_reference' => $this->reference($record, $id),
                'source_type' => $this->journalType($type),
                'source_id' => $id,
                'effective_date' => $inspection['effective_date'],
                'amount' => $record->amount,
                'performed_by' => $actor->getAuthIdentifier(),
                'performed_at' => now(),
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'dependency_snapshot' => $inspection['dependencies'],
                'before_snapshot' => ['source' => $before, 'fund_transaction' => $fundBefore, 'journal' => $journal],
                'metadata' => ['window_days' => $inspection['window_days']],
            ]);
        });
    }

    private function lockSource(string $type, int $id): ?object
    {
        return match ($type) {
            'expense' => Expense::whereKey($id)->lockForUpdate()->first(),
            default => FundTransaction::whereKey($id)->lockForUpdate()->first(),
        };
    }

    private function resolverType(string $type): string { return $type === 'expense' ? 'expense' : 'fund_transaction'; }

    private function journalType(string $type): string
    {
        return match ($type) {
            'expense' => 'expense', 'other_income' => 'income',
            'owner_capital' => 'owner_capital', default => 'owner_withdrawal',
        };
    }

    private function sourceExists(string $type, int $id): bool
    {
        return match ($type) {
            'expense' => Expense::whereKey($id)->exists(),
            default => FundTransaction::whereKey($id)->exists(),
        };
    }

    private function reference(object $record, int $id): string { return $record instanceof Expense ? $record->title : strtoupper((string) $record->source).' #'.$id; }

    private function snapshot(?object $record): ?array
    {
        return $record?->getAttributes();
    }
}
