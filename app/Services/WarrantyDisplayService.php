<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductWarrantyTier;

class WarrantyDisplayService
{
    public function getDisplayableTiers(Product $product, ?int $variantId = null): array
    {
        // Respect product-level warranty_method setting
        $method = $product->warranty_method ?? 'active';
        if ($method === 'inactive' || $method === 'hidden') {
            return [];
        }

        $query = ProductWarrantyTier::where('product_id', $product->id)
            ->where('is_active', true)->orderBy('sort_order');
        if ($variantId) { $query->forVariant($variantId); } else { $query->global(); }
        $tiers = $query->get();
        $basePrice = $product->new_price ?? $product->old_price ?? 0;
        $displayable = [];

        foreach ($tiers as $tier) {
            $adj = (float) ($tier->additional_cost ?? 0);
            $finalPrice = $basePrice + $adj;
            $label = $tier->warranty_days > 0 ? $tier->tier_name : $tier->tier_name;
            $badge = $adj < 0 ? 'Save '.abs($adj).' TK!' : ($adj > 0 ? '+'.$adj.' TK' : ($tier->badge ?? null));
            $type = match ($tier->warranty_type) {
                'none' => 'no_warranty', 'supplier_warranty' => 'with_warranty',
                default => 'extra_warranty',
            };
            if ($tier->warranty_type === 'supplier_warranty') {
                $sw = $product->supplierWarranties()->where('is_transferable',true)
                    ->where('warranty_end_date','>',now())->first();
                if (!$sw) continue;
                $days = $sw->remaining_days;
                $label = $tier->tier_name;
            } else { $days = $tier->warranty_days; }

            $displayable[] = [
                'id'=>$tier->id, 'type'=>$type, 'variant_id'=>$tier->variant_id,
                'label'=>$label, 'badge'=>$badge, 'warranty_days'=>$days,
                'additional_cost'=>$adj, 'final_price'=>$finalPrice,
                'formatted_price'=>number_format($finalPrice,2).' TK',
                'features'=>$tier->features??[], 'is_default'=>$tier->is_default ?: ($type==='no_warranty' || $type==='with_warranty'),
                'is_global'=>$tier->is_global,
            ];
        }
        $order=['with_warranty'=>0,'no_warranty'=>1,'extra_warranty'=>2];
        usort($displayable,fn($a,$b)=>( $order[$a['type']]??99)<=>($order[$b['type']]??99));

        // If no tier is explicitly marked as default, default to the first one
        $hasDefault = false;
        foreach ($displayable as $t) { if ($t['is_default']) { $hasDefault = true; break; } }
        if (!$hasDefault && !empty($displayable)) {
            $displayable[0]['is_default'] = true;
        }

        return $displayable;
    }

    public function hasAnyWarrantyOptions(Product $product, ?int $variantId = null): bool
    {
        // Respect product-level warranty_method setting
        $method = $product->warranty_method ?? 'active';
        if ($method === 'inactive' || $method === 'hidden') {
            return false;
        }

        $q = ProductWarrantyTier::where('product_id',$product->id)->where('is_active',true);
        if($variantId) $q->forVariant($variantId); else $q->global();
        return $q->exists();
    }
}
