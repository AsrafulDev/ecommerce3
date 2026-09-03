<?php

namespace App\Helpers;

use App\Models\OrderDetails;
use App\Models\ProductWarrantyTier;
use App\Models\WarrantySale;
use App\Enums\WarrantySaleStatus;
use Cart;

class OrderHelper
{
    public static function saveOrderDetails($order)
    {
        foreach (Cart::instance('shopping')->content() as $cart) {
            $detail = new OrderDetails();
            $detail->order_id = $order->id;
            $detail->product_id = $cart->id;
            $detail->product_name = $cart->name;
            $detail->purchase_price = $cart->options->purchase_price ?? null;
            $detail->sale_price = $cart->price;
            $detail->qty = $cart->qty;

            $detail->product_color = $cart->options->color_id ?? null;
            $detail->product_size = $cart->options->size_id ?? null;
            $detail->variant_price_id = $cart->options->variant_price_id ?? null;

            // � Wholesale / Product Discount
            $wholesaleDiscount = (float) ($cart->options->wholesale_discount ?? 0);
            $detail->product_discount = $wholesaleDiscount;

            // �🛡️ Warranty
            $warrantyAdj = $cart->options->warranty_adjustment ?? 0;
            if ($cart->options->warranty_tier_id ?? null) {
                $tier = ProductWarrantyTier::find($cart->options->warranty_tier_id);
                if ($tier && $tier->is_active) {
                    $detail->warranty_tier_id = $tier->id;
                    $detail->warranty_price   = $warrantyAdj;
                }
            }

            $detail->save();

            // 🛡️ Create WarrantySale record
            if ($detail->warranty_tier_id) {
                $tier = ProductWarrantyTier::find($detail->warranty_tier_id);
                if ($tier && $tier->warranty_days > 0) {
                    $startDate = null;
                    $endDate   = null;
                    $supplierWarrantyId = null;

                    // For supplier warranty: find available supplier warranty from purchases
                    if ($tier->warranty_type === 'supplier_warranty') {
                        $sw = \App\Models\SupplierWarranty::where('product_id', $detail->product_id)
                            ->where('is_transferable', true)
                            ->where('warranty_end_date', '>', now())
                            ->orderBy('warranty_end_date', 'asc')
                            ->first();
                        if ($sw) {
                            $supplierWarrantyId = $sw->id;
                            $startDate = now();
                            $endDate   = $sw->warranty_end_date;
                        }
                    } else {
                        // Extended warranty: starts from delivery
                        $startDate = now();
                        $endDate   = now()->addDays($tier->warranty_days);
                    }

                    WarrantySale::updateOrCreate(
                        ['order_detail_id' => $detail->id],
                        [
                            'order_id'                 => $order->id,
                            'product_warranty_tier_id' => $tier->id,
                            'customer_id'              => $order->customer_id,
                            'product_id'               => $detail->product_id,
                            'supplier_warranty_id'     => $supplierWarrantyId,
                            'warranty_type'            => $tier->warranty_type,
                            'warranty_days'            => $tier->warranty_days,
                            'warranty_start_date'      => $startDate,
                            'warranty_end_date'        => $endDate,
                            'warranty_price'           => $warrantyAdj,
                            'status'                   => WarrantySaleStatus::ACTIVE->value,
                        ]
                    );
                }
            }
        }

        // NOTE: cart clearing moved to the CALLERS so it only happens AFTER the
        // checkout DB transaction commits — a rollback must never empty the cart.
    }
}

