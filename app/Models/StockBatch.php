<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'variant_price_id', 'purchase_id', 'supplier_id',
        'batch_no', 'quantity', 'remaining_qty', 'unit_cost', 'selling_price',
        'mfg_date', 'exp_date', 'type', 'reference_type', 'reference_id'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'remaining_qty' => 'integer',
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

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id');
    }
}
