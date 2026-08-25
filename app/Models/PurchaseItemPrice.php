<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItemPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_item_id',
        'variant_price_id',
        'selling_price',
        'mrp',
        'wholesale_price',
        'wholesale_tiers',
        'warranty_tiers',
    ];

    protected function casts(): array
    {
        return [
            'selling_price'    => 'decimal:2',
            'mrp'              => 'decimal:2',
            'wholesale_price'  => 'decimal:2',
            'wholesale_tiers'  => 'array',
            'warranty_tiers'   => 'array',
        ];
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id');
    }
}
