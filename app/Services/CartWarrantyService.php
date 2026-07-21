<?php

namespace App\Services;

use App\Models\ProductWarrantyTier;
use Illuminate\Support\Collection;

class CartWarrantyService
{
    /**
     * Validate that the selected warranty tiers are still valid before checkout.
     *
     * @param  Collection  $cartItems
     * @return array  list of error messages (empty = valid)
     */
    public function validateCartWarranties(Collection $cartItems): array
    {
        $errors = [];

        foreach ($cartItems as $item) {
            if (empty($item->warranty_tier_id)) {
                continue;
            }

            $tier = ProductWarrantyTier::find($item->warranty_tier_id);

            if (!$tier || !$tier->is_active) {
                $productName = $item->product->name ?? 'Item';
                $errors[] = "{$productName}: Selected warranty is no longer available. Please choose another option.";
                continue;
            }

            if (isset($item->warranty_price_at_add) && $tier->price != $item->warranty_price_at_add) {
                // Auto-update to latest admin price
                $item->update(['warranty_price' => $tier->price]);
            }
        }

        return $errors;
    }

    /**
     * Get available warranty tiers for a cart item (e.g., when changing warranty in cart).
     */
    public function getTiersForCartItem($productId): array
    {
        return ProductWarrantyTier::where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($tier) => [
                'id'              => $tier->id,
                'label'           => $tier->tier_name,
                'warranty_days'   => $tier->warranty_days,
                'price'           => $tier->price,
                'formatted_price' => number_format($tier->price, 2) . ' TK',
            ])
            ->toArray();
    }
}
