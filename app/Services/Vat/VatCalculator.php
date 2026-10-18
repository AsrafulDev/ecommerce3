<?php

namespace App\Services\Vat;

use App\Models\GeneralSetting;

/**
 * VAT for one line. Two owner switches gate everything: VAT must be enabled AND the
 * owner must have chosen whether prices include VAT. Until both are set, no line gets
 * a VAT snapshot, so existing sales and purchases are not changed.
 */
class VatCalculator
{
    public function enabled(): bool
    {
        $settings = GeneralSetting::query()->first(['vat_enabled', 'vat_prices_include_vat']);

        return (bool) ($settings?->vat_enabled) && $settings?->vat_prices_include_vat !== null;
    }

    public function pricesIncludeVat(): bool
    {
        return (bool) GeneralSetting::query()->value('vat_prices_include_vat');
    }

    /**
     * Splits a line amount into taxable value and VAT, rounded to 2 decimals.
     * Inclusive: the amount already holds the VAT. Exclusive: VAT is added on top.
     *
     * @return array{taxable: float, vat: float}
     */
    public function split(float $amount, float $ratePercent, bool $inclusive): array
    {
        if ($ratePercent < 0) {
            throw new \InvalidArgumentException('A VAT rate cannot be negative.');
        }

        $amount = round($amount, 2);

        if ($inclusive) {
            $vat = round($amount * $ratePercent / (100 + $ratePercent), 2);

            return ['taxable' => round($amount - $vat, 2), 'vat' => $vat];
        }

        return ['taxable' => $amount, 'vat' => round($amount * $ratePercent / 100, 2)];
    }

    /**
     * The snapshot stored on a line, or null when VAT does not apply: the switches are
     * off, or the product has no rate set.
     *
     * @return array{vat_rate: float, taxable_value: float, vat_amount: float}|null
     */
    public function snapshot(float $lineAmount, ?float $rate): ?array
    {
        if ($rate === null || !$this->enabled()) {
            return null;
        }

        $split = $this->split($lineAmount, $rate, $this->pricesIncludeVat());

        return [
            'vat_rate'      => $rate,
            'taxable_value' => $split['taxable'],
            'vat_amount'    => $split['vat'],
        ];
    }
}
