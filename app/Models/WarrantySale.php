<?php

namespace App\Models;

use App\Enums\WarrantySaleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_detail_id',
        'product_warranty_tier_id',
        'customer_id',
        'product_id',
        'supplier_warranty_id',
        'warranty_type',
        'warranty_days',
        'warranty_start_date',
        'warranty_end_date',
        'warranty_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'warranty_days'       => 'integer',
            'warranty_start_date' => 'date',
            'warranty_end_date'   => 'date',
            'warranty_price'      => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetails::class, 'order_detail_id');
    }

    public function productWarrantyTier()
    {
        return $this->belongsTo(ProductWarrantyTier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplierWarranty()
    {
        return $this->belongsTo(SupplierWarranty::class);
    }

    public function claims()
    {
        return $this->hasMany(WarrantyClaim::class, 'warranty_sale_id');
    }

    public function activeClaim()
    {
        return $this->hasOne(WarrantyClaim::class, 'warranty_sale_id')
                    ->whereNotIn('status', ['resolved', 'rejected', 'cancelled']);
    }

    // ── Accessors ─────────────────────────────

    public function getRemainingDaysAttribute(): int
    {
        if (!$this->warranty_end_date) {
            return 0;
        }
        return max(0, (int) now()->diffInDays($this->warranty_end_date, false));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->remaining_days <= 0;
    }

    public function getCanClaimAttribute(): bool
    {
        return !$this->is_expired
            && $this->status === WarrantySaleStatus::ACTIVE->value
            && !$this->activeClaim;
    }

    public function getWarrantyProgressPercentAttribute(): float
    {
        if ($this->warranty_days <= 0) {
            return 100;
        }
        $elapsed = now()->diffInDays($this->warranty_start_date);
        return min(100, round(($elapsed / $this->warranty_days) * 100, 1));
    }

    // ── Boot ──────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $sale) {
            if ($sale->warranty_start_date && $sale->warranty_days > 0) {
                $sale->warranty_end_date = $sale->warranty_start_date->copy()->addDays($sale->warranty_days);
            }
        });
    }
}
