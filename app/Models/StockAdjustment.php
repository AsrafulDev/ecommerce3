<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'product_id', 'variant_price_id', 'type', 'quantity',
        'current_stock', 'new_stock', 'reason',
        'reference_type', 'reference_id', 'created_by'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'current_stock' => 'integer',
            'new_stock' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
