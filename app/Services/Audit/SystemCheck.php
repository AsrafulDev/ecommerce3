<?php

namespace App\Services\Audit;

use App\Models\FundTransaction;
use App\Models\Product;
use App\Models\StockBatch;
use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\CogsCalculator;
use App\Services\Vat\VatCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit of the whole system. It finds problems and says which ones can be repaired
 * safely. It changes nothing.
 */
class SystemCheck
{
    /** @var array<int, array<string, mixed>> */
    private array $extra = [];

    /**
     * @return array<int, array{key: string, title: string, count: int, samples: array<int, string>, repairable: bool, note: string}>
     */
    public function run(): array
    {
        $checks = [
            $this->stockDrift(),
            $this->journalGaps(),
        ];
        array_push($checks, ...$this->extra);
        $checks[] = $this->vatGaps();

        return $checks;
    }

    private function stockDrift(): array
    {
        $batchTotals = StockBatch::query()
            ->selectRaw('product_id, SUM(remaining_qty) AS qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $drift = Product::query()->get(['id', 'name', 'stock'])->filter(
            fn ($p) => (int) $p->stock !== (int) ($batchTotals[$p->id] ?? 0)
        );

        return [
            'key'        => 'stock_drift',
            'title'      => 'Product stock that does not match its batches',
            'count'      => $drift->count(),
            'samples'    => $drift->take(5)->map(fn ($p) => $p->name.': shows '.$p->stock.', batches hold '.($batchTotals[$p->id] ?? 0))->values()->all(),
            'repairable' => true,
            'note'       => 'Repair sets the product stock to the batch total. Batches are the source of truth; no batch is changed.',
        ];
    }

    private function journalGaps(): array
    {
        $gateway = app(AdvancedAccountingGateway::class);

        if (!$gateway->enabled()) {
            return [
                'key' => 'journals', 'title' => 'Advanced accounting journals', 'count' => 0, 'samples' => [],
                'repairable' => false, 'note' => 'Advanced accounting is off. Nothing to journal.',
            ];
        }
        if (!$gateway->readyForLivePosting()) {
            return [
                'key' => 'journals', 'title' => 'Advanced accounting opening', 'count' => 1, 'samples' => ['No opening journal is posted yet.'],
                'repairable' => false, 'note' => 'Post the opening balances first. Journals are not created before that.',
            ];
        }

        $expenses = DB::table('expenses as e')
            ->leftJoin('accounting_journal_entries as j', fn ($join) => $join->on('j.source_id', '=', 'e.id')
                ->where('j.source_type', 'expense')->where('j.status', 'posted'))
            ->whereNotNull('e.fund_transaction_id')
            ->whereNull('j.id')
            ->count();

        $withoutCash = DB::table('expenses')->whereNull('fund_transaction_id')->count();

        $moneyIns = FundTransaction::query()
            ->where('direction', 'in')->where('source', 'manual_add')
            ->whereNotNull('transaction_category')->count();

        $this->extra = [[
            'key'        => 'expenses_without_cash',
            'title'      => 'Expenses with no cash row',
            'count'      => $withoutCash,
            'samples'    => [],
            'repairable' => false,
            'note'       => 'Recorded as expenses but no cash movement exists. Whether they were paid, and from which account, is a decision. Not journaled.',
        ]];

        return [
            'key'        => 'journals',
            'title'      => 'Expenses without a journal',
            'count'      => $expenses,
            'samples'    => $expenses > 0 ? ['Expenses recorded but not yet in the books.'] : [],
            'repairable' => true,
            'note'       => 'Repair posts the missing expense journals, as the expense screen would. Money-in rows are not changed (their nature is a decision).',
        ];
    }

    private function vatGaps(): array
    {
        $count = app(VatCalculator::class)->enabled()
            ? DB::table('order_details as d')->join('orders as o', 'o.id', '=', 'd.order_id')
                ->whereIn('o.order_status', CogsCalculator::RECOGNIZED_STATUSES)
                ->whereNull('d.vat_amount')->count()
            : 0;

        return [
            'key'        => 'vat',
            'title'      => 'Sale lines with no VAT recorded (VAT is on)',
            'count'      => $count,
            'samples'    => [],
            'repairable' => false,
            'note'       => 'Not repaired automatically. These lines were sold without a VAT rate or before VAT was on. Their VAT is never recalculated silently: set the rate and confirm the treatment first.',
        ];
    }
}
