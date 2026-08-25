<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchWholesalePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_batch_id',
        'variant_price_id',
        'min_quantity',
        'max_quantity',
        'wholesale_price',
    ];

    protected function casts(): array
    {
        return [
            'variant_price_id' => 'integer',
            'min_quantity'     => 'integer',
            'max_quantity'     => 'integer',
            'wholesale_price'  => 'decimal:2',
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
}
