<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\OrderDetails;
use App\Models\StockBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit of legacy sales. It lists the sales whose stock, serial and restock records do not agree, so they can
 * be reviewed by hand before any reset or repair. It only reads: no row is written, updated or locked, and no
 * transaction is opened. It is safe to run on the live database.
 */
class SalesLegacyAudit extends Command
{
    protected $signature = 'sales:legacy-audit {--sample=10 : Ids listed per check}';

    protected $description = 'Read-only report of legacy sales whose stock, serial and restock records disagree (changes nothing)';

    public function handle(): int
    {
        $sample = max(1, (int) $this->option('sample'));
        $active = collect(OrderStatus::cases())->filter(fn (OrderStatus $s) => $s->consumesStock())->map(fn ($s) => $s->value)->values()->all();
        $closed = [OrderStatus::CANCELLED->value, OrderStatus::RETURNED->value];

        $checks = [
            'Active sales with no recorded stock allocation' => $this->withoutAllocation($active),
            'Active sales whose recorded allocation does not match the quantity' => $this->mismatchedAllocation($active),
            'Active warranty sales with a serial not recorded as sold in any batch' => $this->unrecordedSerials($active),
            'Cancelled or returned sales with an allocation but no restock' => $this->missingRestock($closed),
            'Products whose stock differs from their batches' => $this->stockDrift(),
            'Cancelled or returned sales that still carry a courier consignment' => $this->closedWithCourier($closed),
        ];

        $rows = [];
        foreach ($checks as $label => $ids) {
            $rows[] = [
                $label,
                count($ids),
                $ids === [] ? '-' : implode(', ', array_slice($ids, 0, $sample)).(count($ids) > $sample ? ' ...' : ''),
            ];
        }

        $this->table(['Check', 'Found', 'Ids (sample)'], $rows);
        $this->line('Read-only: nothing was changed.');

        return self::SUCCESS;
    }

    /** Active sales with no batch allocation recorded at all: the stock they took out cannot be traced. */
    private function withoutAllocation(array $active): array
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereIn('o.order_status', $active)
            ->where(fn ($q) => $q->whereNull('d.batch_ids')->orWhereIn('d.batch_ids', ['', '[]', 'null']))
            ->distinct()
            ->orderBy('o.id')
            ->pluck('o.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Active sales whose recorded allocation is unreadable, or does not add up to the quantity sold. */
    private function mismatchedAllocation(array $active): array
    {
        $ids = [];

        OrderDetails::query()
            ->whereIn('order_id', DB::table('orders')->whereIn('order_status', $active)->select('id'))
            ->whereNotNull('batch_ids')
            ->chunkById(500, function ($details) use (&$ids) {
                foreach ($details as $detail) {
                    if (empty($detail->batch_ids)) {
                        continue; // Counted by withoutAllocation().
                    }

                    $allocation = $detail->stockAllocation();
                    if ($allocation === null || array_sum(array_column($allocation, 'qty')) !== (int) $detail->qty) {
                        $ids[$detail->order_id] = true;
                    }
                }
            });

        return array_map('intval', array_keys($ids));
    }

    /** Active warranty sales carrying a serial that no batch of the product lists as sold. */
    private function unrecordedSerials(array $active): array
    {
        $sold = [];
        StockBatch::query()->whereNotNull('sn_sold')->chunkById(500, function ($batches) use (&$sold) {
            foreach ($batches as $batch) {
                foreach ((array) $batch->sn_sold as $serial) {
                    $sold[$batch->product_id][(string) $serial] = true;
                }
            }
        });

        $ids = [];
        DB::table('warranty_sales as w')
            ->join('orders as o', 'o.id', '=', 'w.order_id')
            ->whereIn('o.order_status', $active)
            ->whereIn('w.status', ['active', 'claimed'])
            ->whereNotNull('w.serial_numbers')
            ->select('w.id', 'w.order_id', 'w.product_id', 'w.serial_numbers')
            ->orderBy('w.id')
            ->chunk(500, function ($rows) use (&$ids, $sold) {
                foreach ($rows as $row) {
                    foreach ((array) json_decode((string) $row->serial_numbers, true) as $serial) {
                        if (! isset($sold[$row->product_id][(string) $serial])) {
                            $ids[$row->order_id] = true;
                        }
                    }
                }
            });

        return array_map('intval', array_keys($ids));
    }

    /** Cancelled or returned sales that took stock out, but have no restock batch for the order. */
    private function missingRestock(array $closed): array
    {
        $restocked = DB::table('stock_batches')
            ->where('reference_type', 'sale_return')
            ->distinct()
            ->pluck('reference_id')
            ->flip()
            ->all();

        $ids = [];
        OrderDetails::query()
            ->whereIn('order_id', DB::table('orders')->whereIn('order_status', $closed)->select('id'))
            ->whereNotNull('batch_ids')
            ->chunkById(500, function ($details) use (&$ids, $restocked) {
                foreach ($details as $detail) {
                    if (! empty($detail->batch_ids) && ! isset($restocked[$detail->order_id])) {
                        $ids[$detail->order_id] = true;
                    }
                }
            });

        return array_map('intval', array_keys($ids));
    }

    /** Products whose denormalised stock is not the sum of their batches (the drift `stock:sync-from-batches` fixes). */
    private function stockDrift(): array
    {
        return DB::table('products as p')
            ->leftJoinSub(
                DB::table('stock_batches')->selectRaw('product_id, SUM(remaining_qty) AS batch_qty')->groupBy('product_id'),
                'b',
                'b.product_id',
                '=',
                'p.id'
            )
            ->whereRaw('COALESCE(p.stock, 0) <> COALESCE(b.batch_qty, 0)')
            ->orderBy('p.id')
            ->pluck('p.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Cancelled or returned sales that still have a courier consignment: the goods may still be with the courier. */
    private function closedWithCourier(array $closed): array
    {
        return DB::table('orders')
            ->whereIn('order_status', $closed)
            ->where('courier_tracking_id', '<>', '')
            ->whereNotNull('courier_tracking_id')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
