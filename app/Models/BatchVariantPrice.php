<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchVariantPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_batch_id',
        'variant_price_id',
        'price',
        'old_price',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'old_price' => 'decimal:2',
            'stock'     => 'integer',
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
