<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchWarrantyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_batch_id',
        'variant_price_id',
        'warranty_tier_id',
        'additional_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variant_price_id' => 'integer',
            'additional_cost'  => 'decimal:2',
            'is_active'        => 'boolean',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id');
    }

    public function tier()
    {
        return $this->belongsTo(ProductWarrantyTier::class, 'warranty_tier_id');
    }
}
