<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'variant_price_id', 'purchase_id', 'supplier_id',
        'batch_no', 'quantity', 'remaining_qty', 'sn_stock', 'sn_sold',
        'unit_cost', 'selling_price',
        'mfg_date', 'exp_date', 'type', 'reference_type', 'reference_id'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'remaining_qty' => 'integer',
            'sn_stock' => 'array',
            'sn_sold' => 'array',
            'unit_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'mfg_date' => 'date',
            'exp_date' => 'date',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get remaining supplier warranty days for this batch (if any).
     */
    public function getSupplierWarrantyDaysAttribute(): ?int
    {
        if (!$this->purchase_id) return null;
        $sw = \App\Models\SupplierWarranty::whereHas('purchaseItem', function ($q) {
            $q->where('purchase_id', $this->purchase_id)
              ->where('product_id', $this->product_id);
        })->where('warranty_end_date', '>', now())->first();
        return $sw ? $sw->remaining_days : null;
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id');
    }
}
