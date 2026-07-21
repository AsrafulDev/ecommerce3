<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductWarrantyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'tier_name',
        'warranty_type',
        'warranty_days',
        'price',
        'additional_cost',
        'is_active',
        'sort_order',
        'badge',
        'features',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'variant_id'      => 'integer',
            'warranty_days'   => 'integer',
            'price'           => 'decimal:2',
            'additional_cost' => 'decimal:2',
            'is_active'       => 'boolean',
            'sort_order'      => 'integer',
            'features'        => 'array',
            'is_default'      => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_id');
    }

    public function warrantySales()
    {
        return $this->hasMany(WarrantySale::class, 'product_warranty_tier_id');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeOrdered($query) { return $query->orderBy('sort_order'); }
    public function scopeGlobal($query) { return $query->whereNull('variant_id'); }

    public function scopeForVariant($query, $variantId)
    {
        return $query->where(function ($q) use ($variantId) {
            $q->whereNull('variant_id')->orWhere('variant_id', $variantId);
        });
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->warranty_days === 0) return 'No Warranty';
        return $this->warranty_days . ' Days — ' . $this->tier_name;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' TK';
    }

    public function getIsGlobalAttribute(): bool
    {
        return $this->variant_id === null;
    }
}
