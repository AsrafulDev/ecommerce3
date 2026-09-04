<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dedicated serial-number list per batch (owner spec D1).
 *  - stock_sn : serial numbers received / available in this batch
 *  - sold_sn  : serial numbers sold/assigned from this batch
 */
class BatchSnList extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'purchase_id',
        'batch_id',
        'stock_sn',
        'sold_sn',
    ];

    protected function casts(): array
    {
        return [
            'stock_sn' => 'array',
            'sold_sn'  => 'array',
        ];
    }

    // ── Relationships ─────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }
}
