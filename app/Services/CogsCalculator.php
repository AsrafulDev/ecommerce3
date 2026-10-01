<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Support\Collection;

/**
 * THE single COGS rule for Lite / operational profit (Commerce-owned; knows
 * nothing about journals, debits or credits).
 *
 * ── Cost rule (per order_details line) ──────────────────────────────────────
 *   1. A stored realized cost (order_details.cogs, written by the stock engine
 *      at stock-out time) is authoritative. It is the TOTAL cost of the line
 *      (qty × blended unit cost), NOT a per-unit figure.
 *   2. Otherwise the line's own snapshot (purchase_price × qty) is used.
 *      purchase_price is the per-unit cost captured on the line when the order
 *      was made, so multiplying by qty here is correct, and the snapshot never
 *      changes when Product.purchase_price is edited later — historical COGS
 *      stays historical.
 *   3. The live Product.purchase_price is deliberately NOT consulted. Reading
 *      today's cost for a past sale silently rewrites history, and the column
 *      is NOT NULL anyway, so a genuinely missing snapshot reports 0 and is a
 *      data bug to fix at source, not to paper over with a drifting price.
 *
 * Discounts, shipping and tax never enter COGS: they are revenue-side money,
 * not the cost of the goods handed over.
 *
 * ── Recognition rule ────────────────────────────────────────────────────────
 * A sale (and its COGS, from the SAME order set, so they always share one
 * reporting period) is recognized when:
 *   order_status ∈ {delivered, completed}  AND  created_at ∈ [from, to].
 *
 * CURRENT LIMITATION — there is no delivery timestamp on orders.
 *   orders has no delivered_at / completed_at / paid_at; nothing ever writes
 *   one. `updated_at` cannot be the recognition date: any later, unrelated
 *   edit (address fix, note, refund flag) moves it, and would silently drag
 *   already-reported COGS between periods. `created_at` is immutable, so a
 *   period's numbers never change under you; the cost is that an order created
 *   in January and delivered in February lands in January's profit.
 *   Long term: add a one-time `delivered_at`, stamped by OrderStatusService on
 *   the first transition into a recognized status, backfill existing
 *   recognized orders, and switch recognizedOrders() to it. Until then this
 *   class is the one place that change happens.
 */
class CogsCalculator
{
    /** Statuses that count as a realized sale (and therefore realized COGS). */
    public const RECOGNIZED_STATUSES = ['delivered', 'completed'];

    /**
     * Recognized orders in [from, to] on the one recognition basis
     * (created_at). Both the sales figure and the COGS figure for a report
     * must be built from exactly this set.
     */
    public function recognizedOrders(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection
    {
        $query = Order::query()->whereIn('order_status', self::RECOGNIZED_STATUSES);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->get();
    }

    /**
     * Realized COGS of one order_details line, per the cost rule above.
     */
    public function lineCogs(OrderDetails $row): float
    {
        $realized = (float) ($row->cogs ?? 0);

        if ($realized > 0) {
            return round($realized, 2);
        }

        return round((float) ($row->purchase_price ?? 0) * (int) ($row->qty ?? 0), 2);
    }

    /**
     * COGS for a set of already-recognized orders — the same rows the sales
     * figure was taken from.
     *
     * @param  Collection<int, Order>|array<int, Order|int>  $orders  order models or ids
     */
    public function cogsForOrders(Collection|array $orders): float
    {
        $ids = $orders instanceof Collection
            ? $orders->pluck('id')->all()
            : array_map(fn ($o) => $o instanceof Order ? $o->id : $o, $orders);

        if (empty($ids)) {
            return 0.0;
        }

        $total = 0.0;

        OrderDetails::query()
            ->whereIn('order_id', $ids)
            ->select('id', 'cogs', 'purchase_price', 'qty')
            ->chunkById(500, function ($rows) use (&$total) {
                foreach ($rows as $row) {
                    $total += $this->lineCogs($row);
                }
            });

        return round($total, 2);
    }

    /**
     * Convenience: recognized orders in the period and their COGS together,
     * so a caller can never mix one from period A and the other from period B.
     *
     * @return array{orders:Collection<int,Order>, sales:float, cogs:float, gross_profit:float}
     */
    public function periodProfit(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null, ?string $salesColumn = 'amount'): array
    {
        $orders = $this->recognizedOrders($from, $to);
        $sales  = round((float) $orders->sum(fn ($o) => (float) ($o->{$salesColumn} ?? 0)), 2);
        $cogs   = $this->cogsForOrders($orders);

        return [
            'orders'       => $orders,
            'sales'        => $sales,
            'cogs'         => $cogs,
            'gross_profit' => round($sales - $cogs, 2),
        ];
    }
}
