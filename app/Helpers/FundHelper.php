<?php

namespace App\Helpers;

use App\Models\FundTransaction;
use App\Models\Order;

class FundHelper
{
    public static function balance()
    {
        $in  = FundTransaction::where('direction', 'in')->sum('amount');
        $out = FundTransaction::where('direction', 'out')->sum('amount');
        return $in - $out;
    }

    /**
     * ⭐ Guarded full-amount sale credit (UPDATE-PLAN Phase 4).
     * One 'in' sale row per order (source + source_id) with a balance snapshot.
     * Returns true when created, false when the order is already credited (so a
     * webhook / bulk update / process page can never double-credit).
     *
     * @param Order $order
     * @param string|null $note
     * @param int|null $userId
     * @return bool
     */
    public static function creditSale(Order $order, ?string $note = null, ?int $userId = null): bool
    {
        if (FundTransaction::where('source', 'sale')->where('source_id', $order->id)->exists()) {
            return false;
        }

        $amount        = (float) $order->amount;
        $balanceBefore = self::balance();
        $createdBy     = $userId ?? (auth()->id() ?? 1);

        // balance_* are NOT in FundTransaction::$fillable → set them directly.
        $tx               = new FundTransaction();
        $tx->direction    = 'in';
        $tx->source       = 'sale';
        $tx->source_id    = $order->id;
        $tx->amount       = $amount;
        $tx->note         = $note ?? 'Order complete (#' . ($order->invoice_id ?? $order->id) . ')';
        $tx->created_by   = $createdBy;
        $tx->balance_before = $balanceBefore;
        $tx->balance_after  = $balanceBefore + $amount;
        $tx->save();

        return $tx->exists;
    }

    /**
     * ⭐ Guarded refund debit (UPDATE-PLAN Phase 4).
     * One 'out' refund row per refund (source + source_id) with a balance snapshot.
     *
     * @param int $sourceId   refunds.id (or order id, per caller convention)
     * @param float $amount
     * @param string|null $note
     * @param int|null $userId
     * @return bool
     */
    public static function debitRefund(int $sourceId, float $amount, ?string $note = null, ?int $userId = null): bool
    {
        if (FundTransaction::where('source', 'refund')->where('source_id', $sourceId)->exists()) {
            return false;
        }

        $balanceBefore = self::balance();
        $createdBy     = $userId ?? (auth()->id() ?? 1);

        // balance_* are NOT in FundTransaction::$fillable → set them directly.
        $tx               = new FundTransaction();
        $tx->direction    = 'out';
        $tx->source       = 'refund';
        $tx->source_id    = $sourceId;
        $tx->amount       = $amount;
        $tx->note         = $note;
        $tx->created_by   = $createdBy;
        $tx->balance_before = $balanceBefore;
        $tx->balance_after  = $balanceBefore - $amount;
        $tx->save();

        return $tx->exists;
    }
}
