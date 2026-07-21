<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SupplierWarranty;

class WarrantyPriceCalculator
{
    /**
     * Calculate all warranty tier prices for a product.
     */
    public function calculate(Product $product, ?SupplierWarranty $supplierWarranty = null): array
    {
        $basePrice = $product->selling_price;
        $tiers     = [];

        // No Warranty
        $tiers['no_warranty'] = [
            'label'       => 'No Warranty',
            'days'        => 0,
            'price'       => round($basePrice * 0.88, 2),
            'savings'     => round($basePrice - ($basePrice * 0.88), 2),
            'description' => 'Buy without warranty coverage at a discounted price.',
        ];

        // Supplier Warranty
        if ($supplierWarranty && $supplierWarranty->is_sellable) {
            $remainingDays = $supplierWarranty->remaining_days;
            $tiers['supplier_warranty'] = [
                'label'       => "{$remainingDays} Days Warranty",
                'days'        => $remainingDays,
                'price'       => $basePrice,
                'savings'     => 0,
                'description' => "Covered by supplier warranty for {$remainingDays} days.",
                'source'      => 'supplier',
                'expires_at'  => $supplierWarranty->warranty_end_date->format('d M, Y'),
            ];
        }

        // Extended Store Warranty
        $extendedDays = config('warranty.default_extended_days', 90);
        $tiers['extended_warranty'] = [
            'label'       => "{$extendedDays} Days Extended Warranty",
            'days'        => $extendedDays,
            'price'       => round($basePrice * 1.12, 2),
            'savings'     => 0,
            'description' => "Extended warranty provided by our store for {$extendedDays} days.",
            'source'      => 'store',
        ];

        return $tiers;
    }

    /**
     * Dynamic margin-based pricing.
     */
    public function calculateWithMargins(
        float $costPrice,
        array $margins = []
    ): array {
        $margins = $margins ?: config('warranty.margins', [
            'no_warranty'       => 0.15,
            'supplier_warranty' => 0.25,
            'extended_warranty' => 0.35,
        ]);

        return [
            'no_warranty'       => round($costPrice * (1 + $margins['no_warranty']), 2),
            'supplier_warranty' => round($costPrice * (1 + $margins['supplier_warranty']), 2),
            'extended_warranty' => round($costPrice * (1 + $margins['extended_warranty']), 2),
        ];
    }
}
