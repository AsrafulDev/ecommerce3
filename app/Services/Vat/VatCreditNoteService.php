<?php

namespace App\Services\Vat;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PurchaseItem;
use App\Models\VatCreditNote;
use Illuminate\Support\Facades\DB;

/**
 * Issues the VAT reversal for returned or cancelled sales and for purchase returns.
 * The amounts come from the snapshots taken at sale or purchase time, so a credit note
 * always reverses exactly the VAT that was declared.
 */
class VatCreditNoteService
{
    /**
     * One credit note per sale. Idempotent: a second call for the same order returns null.
     */
    public function forOrder(Order $order, ?string $reason = null, ?int $userId = null): ?VatCreditNote
    {
        if (VatCreditNote::where('order_id', $order->id)->exists()) {
            return null;
        }

        $lines = OrderDetails::where('order_id', $order->id)->whereNotNull('vat_amount');
        if (!$lines->exists()) {
            return null; // no VAT was declared on this order
        }

        $totals = $lines->selectRaw('SUM(taxable_value) AS taxable, SUM(vat_amount) AS vat')->first();

        return $this->create([
            'type'          => 'sale',
            'order_id'      => $order->id,
            'taxable_value' => round((float) $totals->taxable, 2),
            'vat_amount'    => round((float) $totals->vat, 2),
            'reason'        => $reason ?: 'Sale returned or cancelled: '.($order->invoice_id ?: 'Order #'.$order->id),
            'created_by'    => $userId,
        ]);
    }

    /**
     * One credit note per purchase return event, for the quantity actually returned.
     * The VAT is pro-rated from the line's snapshot.
     */
    public function forPurchaseReturn(PurchaseItem $item, int $qty, ?int $userId = null): ?VatCreditNote
    {
        if ($item->vat_amount === null || (int) $item->qty <= 0 || $qty <= 0) {
            return null;
        }

        $share = $qty / (int) $item->qty;

        return $this->create([
            'type'              => 'purchase',
            'purchase_id'       => $item->purchase_id,
            'purchase_item_id'  => $item->id,
            'qty'               => $qty,
            'taxable_value'     => round((float) $item->taxable_value * $share, 2),
            'vat_amount'        => round((float) $item->vat_amount * $share, 2),
            'reason'            => 'Purchase return: '.$qty.' unit(s)',
            'created_by'        => $userId,
        ]);
    }

    /**
     * Numbers run per year, CN-YYYY-000001. The count is taken under a row lock inside
     * the transaction, so two credit notes cannot share a number.
     */
    protected function create(array $data): VatCreditNote
    {
        return DB::transaction(function () use ($data) {
            $year = now()->format('Y');
            $sequence = VatCreditNote::whereYear('issued_at', $year)->lockForUpdate()->count() + 1;

            return VatCreditNote::create($data + [
                'credit_note_no' => sprintf('CN-%s-%06d', $year, $sequence),
                'issued_at'      => now(),
            ]);
        });
    }
}
