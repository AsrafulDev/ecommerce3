<?php

namespace App\Services\Vat\Challan;

use App\Models\Order;
use App\Models\VatChallan;
use App\Models\VatChallanEvent;
use Illuminate\Support\Facades\DB;

/**
 * Records the buyer's BIN on a sale. The app had no way to enter it before this, and a challan cannot be printed without
 * it. Every change is written to the audit trail, and the BIN cannot change once a challan is issued for the sale.
 */
class ChallanBuyer
{
    public function setBin(Order $order, string $bin, ?int $userId = null, ?string $ip = null): void
    {
        $bin = trim($bin);
        if (! ChallanRules::validBin($bin)) {
            throw new \InvalidArgumentException("The buyer's BIN must be 12 digits.");
        }

        DB::transaction(function () use ($order, $bin, $userId, $ip) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (VatChallan::where('issued_order_id', $locked->id)->exists()) {
                throw new \DomainException('A challan is issued for this sale, so its buyer BIN cannot change.');
            }

            $previous = $locked->buyer_bin;
            if ($previous === $bin) {
                return;
            }

            $locked->buyer_bin = $bin;
            $locked->save();

            VatChallanEvent::record('buyer_bin_set', null, $locked->id, $userId, $ip, [
                'from' => $previous,
                'to' => $bin,
            ]);
        });
    }
}
