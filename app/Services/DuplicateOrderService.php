<?php

namespace App\Services;

use App\Models\Order;
use App\Enums\OrderStatus;

/**
 * Local duplicate-order detection — NO 3rd-party API.
 *
 * An order counts as a duplicate when the SAME phone number (the order's
 * shipping phone, falling back to the customer phone) appears on more than
 * one active (non-cancelled / non-closed) order. The response shape mirrors
 * the old 3rd-party API so the existing views keep working unchanged.
 */
class DuplicateOrderService
{
    /** More than this many orders from the same number ⇒ duplicate. */
    public const MIN_ORDERS = 2;

    /** Terminal statuses excluded from duplicate detection. */
    private const EXCLUDED = [OrderStatus::CANCELLED->value, OrderStatus::CLOSED->value];

    /**
     * Normalise a phone number to its trailing 11 digits (Bangladesh format),
     * stripping spaces, dashes, plus signs and the 880 country prefix.
     */
    public function normalize(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile);
        $digits = (string) $digits;

        // 8801712345678 -> 01712345678
        if (strlen($digits) === 13 && str_starts_with($digits, '880')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    /**
     * All orders placed from a phone number (shipping phone or customer
     * phone), newest first, excluding terminal statuses.
     *
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function ordersFor(string $mobile)
    {
        $digits = $this->normalize($mobile);
        if ($digits === '') {
            return collect();
        }

        return Order::query()
            ->where(function ($q) use ($digits) {
                $q->whereHas('shipping', function ($sq) use ($digits) {
                    $sq->whereRaw(
                        "RIGHT(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+',''),11) = ?",
                        [$digits]
                    );
                })->orWhereHas('customer', function ($cq) use ($digits) {
                    $cq->whereRaw(
                        "RIGHT(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+',''),11) = ?",
                        [$digits]
                    );
                });
            })
            ->whereNotIn('order_status', self::EXCLUDED)
            ->with(['shipping:id,order_id,name,phone,address', 'customer:id,name,phone,email'])
            ->latest()
            ->get();
    }

    /**
     * Compute duplicate-order metrics for a phone number.
     *
     * @return array{
     *     status: string,
     *     is_duplicate: bool,
     *     duplicate_count: int,
     *     duplicate_rate: int,
     *     last_duplicate_date: string|null,
     *     message: string,
     *     details: array
     * }
     */
    public function check(string $mobile): array
    {
        $orders = $this->ordersFor($mobile);
        $total  = $orders->count();

        $isDuplicate = $total >= self::MIN_ORDERS;

        // Heuristic rate: each extra order after the first adds 25% (max 100).
        $rate = $isDuplicate ? min(100, ($total - 1) * 25) : 0;

        $latest = $orders->first();

        $details = $orders->map(fn ($order) => [
            'invoice_id' => $order->invoice_id,
            'date'       => optional($order->created_at)->toDateTimeString(),
            'status'     => $order->order_status,
            'amount'     => $order->amount,
            'name'       => $order->shipping->name ?? $order->customer->name ?? null,
            'phone'      => $order->shipping->phone ?? $order->customer->phone ?? null,
        ])->values()->toArray();

        return [
            'status'              => 'success',
            'is_duplicate'        => $isDuplicate,
            'duplicate_count'     => $total,
            'duplicate_rate'      => $rate,
            'last_duplicate_date' => $latest ? optional($latest->created_at)->toDateTimeString() : null,
            'message'             => $isDuplicate
                ? "এই মোবাইল নাম্বার দিয়ে {$total} টি অর্ডার পাওয়া গেছে।"
                : 'কোনো ডুপ্লিকেট অর্ডার পাওয়া যায়নি।',
            'details'             => $details,
        ];
    }

    /**
     * Tag every order for a phone number with the duplicate flags, then
     * return the computed metrics (used by the AJAX check).
     */
    public function tagOrders(string $mobile): array
    {
        $metrics = $this->check($mobile);

        $this->ordersFor($mobile)->each(function ($order) use ($metrics) {
            $order->is_duplicate_order        = $metrics['is_duplicate'] ? 1 : 0;
            $order->duplicate_order_count     = $metrics['duplicate_count'];
            $order->duplicate_order_rate      = $metrics['duplicate_rate'];
            $order->last_duplicate_order_date = $metrics['last_duplicate_date']
                ? \Carbon\Carbon::parse($metrics['last_duplicate_date'])
                : null;
            $order->save();
        });

        return $metrics;
    }
}
