<?php

namespace App\Services\Audit;

use App\Models\Expense;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\ManualEntryService;
use App\Services\StockManagementService;

/**
 * Runs only the repairs that are safe to repeat: they only add what is missing, or set a cache
 * to the value its source already holds. Nothing else is changed. Everything else is reported
 * by SystemCheck for a decision.
 */
class SystemRepair
{
    /**
     * @return array{stock_products: int, journals: array<string, int>}
     */
    public function run(): array
    {
        $summary = [
            // Product stock is a copy of the batch totals. Setting it from the batches cannot lose stock.
            'stock_products' => app(StockManagementService::class)->syncStockFromBatches(null),
            'journals'       => [],
        ];

        $gateway = app(AdvancedAccountingGateway::class);
        if ($gateway->enabled() && $gateway->readyForLivePosting()) {
            $summary['journals'] = $this->postMissingExpenses();
        }

        return $summary;
    }

    /**
     * Only expenses that have a cash row are posted: a journal that says cash left must match a
     * fund movement. Expenses without one are reported by SystemCheck for a decision.
     *
     * @return array{posted: int, skipped: int}
     */
    private function postMissingExpenses(): array
    {
        $manual = app(ManualEntryService::class);
        $out = ['posted' => 0, 'skipped' => 0];

        Expense::query()->whereNotNull('fund_transaction_id')->orderBy('id')->each(function (Expense $expense) use ($manual, &$out) {
            if (!$manual->expenseNeedsEntry($expense)) {
                return;
            }
            $result = $manual->expense($expense);
            $result->isPosted() ? $out['posted']++ : $out['skipped']++;
        });

        return $out;
    }
}
