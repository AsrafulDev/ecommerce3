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

    // ── Live warranty_days for supplier_warranty tiers ──────────────────
    // Automatically counts down from the linked SupplierWarranty.warranty_end_date.
    // No cron needed — always shows the current remaining days.
    public function getWarrantyDaysAttribute($value): int
    {
        if ($this->warranty_type === \App\Enums\WarrantyType::SUPPLIER_WARRANTY->value) {
            $sw = \App\Models\SupplierWarranty::where('product_id', $this->product_id)
                ->where('is_transferable', true)
                ->where('warranty_end_date', '>', now())
                ->orderBy('warranty_end_date')
                ->first();
            if ($sw) {
                return $sw->remaining_days;
            }
            return 0;
        }
        return (int) $value;
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
