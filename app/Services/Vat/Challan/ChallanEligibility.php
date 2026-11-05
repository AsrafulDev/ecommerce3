<?php

namespace App\Services\Vat\Challan;

use App\Enums\OrderStatus;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\VatChallan;
use App\Models\VatCreditNote;
use App\Services\CogsCalculator;
use App\Services\Vat\VatCalculator;

/**
 * Decides whether one sale can carry a Mushak 6.3 challan. Nothing is issued for a sale that fails a blocker, and
 * warnings are shown without stopping the issue.
 */
class ChallanEligibility
{
    /**
     * @return array{eligible: bool, blockers: list<string>, warnings: list<string>, seller: array<string, ?string>, buyer: array<string, ?string>, lines: int, total_value: float, total_vat: float}
     */
    public function inspect(Order $order): array
    {
        $blockers = [];
        $warnings = [];
        $settings = GeneralSetting::orderBy('id', 'desc')->first();

        $seller = [
            'name' => $settings?->vat_business_name,
            'bin' => $settings?->vat_bin,
            'address' => $settings?->vat_business_address,
        ];

        if (! app(VatCalculator::class)->enabled()) {
            $blockers[] = 'VAT is switched off in VAT/Tax settings.';
        }

        if (! in_array($order->order_status, CogsCalculator::RECOGNIZED_STATUSES, true)) {
            $label = OrderStatus::tryFrom((string) $order->order_status)?->label() ?? (string) $order->order_status;
            $blockers[] = "The sale is {$label}. A challan is issued for a delivered or completed sale.";
        }

        if ($issued = VatChallan::where('issued_order_id', $order->id)->first()) {
            $blockers[] = "Challan {$issued->challan_no} is already issued for this sale.";
        }

        if (VatCreditNote::where('order_id', $order->id)->exists()) {
            $blockers[] = 'A VAT credit note already reverses this sale, so no challan is issued for it.';
        }

        if (! ChallanRules::validBin($seller['bin'])) {
            $blockers[] = 'Your VAT registration number (BIN) is missing or is not 12 digits. Enter it in VAT/Tax settings.';
        }
        if (blank($seller['name']) || blank($seller['address'])) {
            $blockers[] = 'The business name and address are needed on the challan. Enter them in VAT/Tax settings.';
        }

        $shipping = $order->shipping;
        $buyer = [
            'name' => $shipping?->name ?: $order->customer?->name,
            'bin' => $order->buyer_bin,
            'address' => $shipping?->address,
        ];

        if (! ChallanRules::validBin($buyer['bin'])) {
            $blockers[] = 'The buyer\'s BIN is missing or is not 12 digits. Enter it for this sale before issuing.';
        }
        if (blank($buyer['name'])) {
            $blockers[] = 'The buyer\'s name is missing from the delivery details.';
        }
        if (blank($buyer['address'])) {
            $blockers[] = 'The delivery address is missing. It is the destination of supply on the challan.';
        }

        $details = OrderDetails::where('order_id', $order->id)->orderBy('id')->get();
        if ($details->isEmpty()) {
            $blockers[] = 'The sale has no lines.';
        }

        $totalValue = 0.0;
        $totalVat = 0.0;
        foreach ($details as $detail) {
            if ($detail->taxable_value === null || $detail->vat_amount === null || $detail->vat_rate === null) {
                $blockers[] = "\"{$detail->product_name}\": the VAT for this line is not recorded, so it cannot be printed.";

                continue;
            }
            $totalValue += (float) $detail->taxable_value;
            $totalVat += (float) $detail->vat_amount;
        }

        if ((float) $order->shipping_charge > 0) {
            $warnings[] = 'Delivery charges are not lines on the challan. Their VAT treatment is an open question for the practitioner.';
        }
        if ((float) $order->discount > 0) {
            $warnings[] = 'An order-level discount is not shown on the challan lines. Each line carries its own taxable value.';
        }

        return [
            'eligible' => $blockers === [],
            'blockers' => $blockers,
            'warnings' => $warnings,
            'seller' => $seller,
            'buyer' => $buyer,
            'lines' => $details->count(),
            'total_value' => round($totalValue, 2),
            'total_vat' => round($totalVat, 2),
        ];
    }
}
