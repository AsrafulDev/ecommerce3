<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierWarranty extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_item_id',
        'batch_id',
        'product_id',
        'variant_id',
        'supplier_id',
        'warranty_days',
        'warranty_start_date',
        'warranty_end_date',
        'warranty_type',
        'warranty_terms',
        'is_transferable',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'warranty_days'       => 'integer',
            'warranty_start_date' => 'date',
            'warranty_end_date'   => 'date',
            'is_transferable'     => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warrantySales()
    {
        return $this->hasMany(\App\Models\WarrantySale::class, 'supplier_warranty_id');
    }

    // ── Accessors ─────────────────────────────

    public function getRemainingDaysAttribute(): int
    {
        if (!$this->warranty_end_date) {
            return 0;
        }
        return max(0, (int) now()->diffInDays($this->warranty_end_date, false));
    }

    public function getIsValidAttribute(): bool
    {
        return $this->remaining_days > 0;
    }

    public function getIsSellableAttribute(): bool
    {
        return $this->is_valid && $this->is_transferable;
    }
}
