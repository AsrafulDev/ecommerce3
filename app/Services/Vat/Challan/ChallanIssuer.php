<?php

namespace App\Services\Vat\Challan;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\VatChallan;
use App\Models\VatChallanEvent;
use App\Models\VatChallanLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Issues and voids Mushak 6.3 challans. Issue and void each run in one transaction, and the issue locks the sale before
 * it checks eligibility, so two issues for the same sale cannot both succeed.
 */
class ChallanIssuer
{
    public function __construct(
        private readonly ChallanEligibility $eligibility,
        private readonly ChallanNumberer $numberer,
        private readonly ChallanPdf $pdf,
    ) {
    }

    /**
     * @throws ChallanRefused when the sale fails a blocker. Nothing is written in that case, not even a number.
     */
    public function issue(Order $order, ?int $userId = null, ?string $ip = null): VatChallan
    {
        $this->numberer->ensure(ChallanRules::SERIES, now()->year);

        return DB::transaction(function () use ($order, $userId, $ip) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            $inspection = $this->eligibility->inspect($locked);
            if (! $inspection['eligible']) {
                throw new ChallanRefused($inspection['blockers']);
            }

            $now = now();
            $number = ChallanNumberer::format($now->year, $this->numberer->next(ChallanRules::SERIES, $now->year));

            $challan = VatChallan::create([
                'challan_no' => $number,
                'status' => VatChallan::ISSUED,
                'order_id' => $locked->id,
                'issued_order_id' => $locked->id,
                'invoice_no' => $locked->invoice_id,
                'issue_date' => $now->toDateString(),
                'issue_time' => $now->format('H:i:s'),
                'issued_at' => $now,
                'seller_name' => $inspection['seller']['name'],
                'seller_bin' => $inspection['seller']['bin'],
                'seller_address' => $inspection['seller']['address'],
                'buyer_name' => $inspection['buyer']['name'],
                'buyer_bin' => $inspection['buyer']['bin'],
                'buyer_address' => $inspection['buyer']['address'],
                'destination' => $inspection['buyer']['address'],
                'total_value' => $inspection['total_value'],
                'total_vat' => $inspection['total_vat'],
                'total_with_tax' => round($inspection['total_value'] + $inspection['total_vat'], 2),
                'issued_by' => $userId,
            ]);

            $serial = 0;
            foreach (OrderDetails::with('product.brand')->where('order_id', $locked->id)->orderBy('id')->get() as $detail) {
                $serial++;
                $quantity = (float) $detail->qty;
                $value = round((float) $detail->taxable_value, 2);
                $vat = round((float) $detail->vat_amount, 2);
                $brand = $detail->product?->brand?->name;

                VatChallanLine::create([
                    'vat_challan_id' => $challan->id,
                    'serial' => $serial,
                    'order_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'description' => $brand ? "{$detail->product_name} ({$brand})" : (string) $detail->product_name,
                    'unit' => $detail->product?->pro_unit,
                    'quantity' => $quantity,
                    'unit_value' => $quantity > 0 ? round($value / $quantity, 4) : null,
                    'line_value' => $value,
                    'sd_amount' => null,
                    'vat_rate' => round((float) $detail->vat_rate, 2),
                    'vat_amount' => $vat,
                    'line_total' => round($value + $vat, 2),
                ]);
            }

            // The PDF is rendered from the rows just written and stored with its hash. If rendering or storing fails, the
            // whole issue rolls back, so a challan never exists without its printed copy.
            $challan->load('lines');
            $bytes = $this->pdf->render($challan);
            $path = 'vat-challans/'.$challan->challan_no.'.pdf';
            Storage::disk('local')->put($path, $bytes);
            $challan->update(['pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes)]);

            VatChallanEvent::record('issued', $challan->id, $locked->id, $userId, $ip, [
                'challan_no' => $challan->challan_no,
                'invoice_no' => $challan->invoice_no,
                'lines' => $serial,
                'total_value' => (float) $challan->total_value,
                'total_vat' => (float) $challan->total_vat,
                'pdf_sha256' => $challan->pdf_sha256,
            ]);

            return $challan->fresh('lines');
        });
    }

    /**
     * Voids an issued challan. The number is kept, the record stays, and the sale can be challaned again under a new
     * number. A reason is required.
     */
    public function void(VatChallan $challan, string $reason, ?int $userId = null, ?string $ip = null): VatChallan
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reason is needed to void a challan.');
        }

        return DB::transaction(function () use ($challan, $reason, $userId, $ip) {
            $locked = VatChallan::query()->lockForUpdate()->findOrFail($challan->id);

            if (! $locked->isIssued()) {
                throw new \DomainException("Challan {$locked->challan_no} is already voided.");
            }

            $locked->update([
                'status' => VatChallan::VOIDED,
                'issued_order_id' => null,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            VatChallanEvent::record('voided', $locked->id, $locked->order_id, $userId, $ip, [
                'challan_no' => $locked->challan_no,
                'reason' => $reason,
            ]);

            return $locked->fresh('lines');
        });
    }
}
