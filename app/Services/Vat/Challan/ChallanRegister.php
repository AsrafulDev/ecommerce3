<?php

namespace App\Services\Vat\Challan;

use App\Models\Order;
use App\Models\VatChallan;
use App\Services\CogsCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The sales a challan can be issued for in a period, each with its eligibility, and the challans already issued.
 */
class ChallanRegister
{
    /** @return list<array{order: Order, inspection: array<string, mixed>}> */
    public function candidates(Carbon $from, Carbon $to, int $limit = 300): array
    {
        return Order::query()
            ->with('shipping')
            ->whereIn('order_status', CogsCalculator::RECOGNIZED_STATUSES)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order) => ['order' => $order, 'inspection' => app(ChallanEligibility::class)->inspect($order)])
            ->all();
    }

    /** @return Collection<int, VatChallan> */
    public function issued(Carbon $from, Carbon $to): Collection
    {
        return VatChallan::query()
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();
    }
}
