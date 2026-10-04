<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\Refund;
use App\Models\SupplierPayment;

class FinancialTransactionDependencyResolver
{
    public function __construct(private AdvancedAccountingGateway $accounting) {}

    public function resolve(string $type, int $id): array
    {
        $record = match ($type) {
            'expense' => Expense::with('fundTransaction')->find($id),
            'fund_transaction' => FundTransaction::with('logs')->find($id),
            'customer_payment' => OrderPayment::with(['order', 'customer'])->find($id),
            'supplier_payment' => SupplierPayment::with(['purchase', 'supplier', 'fundTransaction'])->find($id),
            'purchase' => Purchase::with(['items', 'payments', 'supplier'])->find($id),
            'refund' => Refund::with(['order', 'customer'])->find($id),
            default => null,
        };

        if (!$record) {
            return ['record' => null, 'dependencies' => [], 'blockers' => ['UNKNOWN_SOURCE']];
        }

        $dependencies = [];
        $blockers = [];

        if ($record instanceof Expense && $record->fund_transaction_id) {
            $dependencies[] = ['type' => 'fund_transaction', 'id' => $record->fund_transaction_id];
            if ($record->isSystemGenerated()) $blockers[] = 'SYSTEM_GENERATED_EXPENSE';
        }
        if ($record instanceof SupplierPayment && $record->fund_transaction_id) {
            $dependencies[] = ['type' => 'fund_transaction', 'id' => $record->fund_transaction_id];
        }
        if ($record instanceof SupplierPayment || $record instanceof OrderPayment || $record instanceof Refund) {
            $dependencies[] = ['type' => 'party', 'id' => $record->customer_id ?? $record->supplier_id ?? null];
        }
        if ($record instanceof Purchase) {
            $dependencies[] = ['type' => 'purchase_items', 'count' => $record->items->count()];
            $dependencies[] = ['type' => 'supplier_payments', 'count' => $record->payments->count()];
            $blockers[] = 'STOCK_DEPENDENCY_UNSAFE';
        }
        if ($record instanceof Refund) {
            $blockers[] = 'REFUND_CHAIN_REQUIRES_REVIEW';
        }
        if ($record instanceof OrderPayment) {
            $dependencies[] = ['type' => 'order', 'id' => $record->order_id];
            $blockers[] = 'PAYMENT_RECONCILIATION_NOT_IMPLEMENTED';
        }

        if ($record instanceof FundTransaction && $record->isSystemLinked()) {
            $blockers[] = 'SYSTEM_LINKED_TRANSACTION';
        }

        if ($record instanceof Expense) {
            $journal = $this->accounting->financialJournalState('expense', $id);
            if (is_array($journal) && isset($journal['blocker'])) $blockers[] = $journal['blocker'];
            if (is_array($journal) && !isset($journal['blocker'])) $dependencies[] = ['type' => 'advanced_journal', 'snapshot' => $journal];
        }

        if ($record instanceof FundTransaction && $record->isEditable()) {
            $type = match ($record->transaction_category?->value) {
                'other_income' => 'income',
                'owner_capital' => 'owner_capital',
                'owner_withdrawal' => 'owner_withdrawal',
                default => null,
            };
            if (!$type) $blockers[] = 'UNCLASSIFIED_MANUAL_MONEY_EVENT';
            if ($type) {
                $journal = $this->accounting->financialJournalState($type, $id);
                if (is_array($journal) && isset($journal['blocker'])) $blockers[] = $journal['blocker'];
                if (is_array($journal) && !isset($journal['blocker'])) $dependencies[] = ['type' => 'advanced_journal', 'snapshot' => $journal];
            }
        }

        return compact('record', 'dependencies', 'blockers');
    }
}
