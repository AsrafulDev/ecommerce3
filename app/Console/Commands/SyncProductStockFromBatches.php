<?php

namespace App\Console\Commands;

use App\Services\StockManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProductStockFromBatches extends Command
{
    protected $signature = 'stock:sync-from-batches
        {--product= : Only sync this product id}
        {--dry-run : Report only, no writes}';

    protected $description = 'Reconcile products.stock (and variant stock) from stock_batches';

    public function handle(StockManagementService $stockService): int
    {
        $productId = $this->option('product') ? (int) $this->option('product') : null;

        // --- Report of mismatches (products whose stock column differs from batches) ---
        $rows = DB::table('products as p')
            ->leftJoin('stock_batches as sb', 'sb.product_id', '=', 'p.id')
            ->where('sb.remaining_qty', '>', 0)
            ->selectRaw('p.id, p.name, p.stock as db_stock, SUM(sb.remaining_qty) as batch_stock')
            ->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->groupBy('p.id', 'p.name', 'p.stock')
            ->havingRaw('p.stock <> SUM(sb.remaining_qty)')
            ->orderBy('p.id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No mismatches found. products.stock already matches batches.');
            return 0;
        }

        $this->info("Mismatched products: {$rows->count()}");
        $this->table(
            ['ID', 'Name', 'products.stock', 'batch sum'],
            $rows->map(fn ($r) => [$r->id, $r->name, $r->db_stock, $r->batch_stock])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes written.');
            return 0;
        }

        $updated = $stockService->syncStockFromBatches($productId);
        $this->info("Done. Updated {$updated} product/variant row(s).");
        return 0;
    }
}
