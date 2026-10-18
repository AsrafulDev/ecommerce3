<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if ($item->vat_amount !== null) {
                return;
            }

            $rate = \App\Models\Product::whereKey($item->product_id)->value('vat_rate');
            $amount = $item->line_total !== null
                ? (float) $item->line_total
                : (float) $item->unit_cost * (int) $item->qty;

            $snapshot = app(\App\Services\Vat\VatCalculator::class)
                ->snapshot($amount, $rate === null ? null : (float) $rate);

            if ($snapshot) {
                $item->fill($snapshot);
            }
        });
    }

    protected $fillable = [
        'purchase_id','product_id','variant_price_id',
        'qty','unit_cost','line_total','returned_qty',
        'custom_field',
        'vat_rate', 'taxable_value', 'vat_amount',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\ProductVariantPrice::class, 'variant_price_id');
    }

    public function supplierWarranties()
    {
        return $this->hasMany(SupplierWarranty::class, 'purchase_item_id');
    }
}
