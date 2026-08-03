<?php

namespace App\Models;

use App\Enums\DamageStatus;
use App\Enums\DamageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'warranty_sale_id',
        'product_id',
        'original_serial_number',
        'replacement_serial_number',
        'damage_type',
        'status',
        'condition_note',
        'accessories',
        'service_cost',
        'damage_cost',
        'resell_price',
        'expense_id',
        'earning_fund_id',
        'received_at',
        'disposed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'service_cost' => 'decimal:2',
            'damage_cost'  => 'decimal:2',
            'resell_price' => 'decimal:2',
            'received_at'  => 'datetime',
            'disposed_at'  => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────

    public function warrantyClaim()
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    public function warrantySale()
    {
        return $this->belongsTo(WarrantySale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function logs()
    {
        return $this->morphMany(\App\Models\ActivityLog::class, 'model')->latest();
    }

    public function earningFund()
    {
        return $this->belongsTo(FundTransaction::class, 'earning_fund_id');
    }

    // ── Scopes ────────────────────────────────

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    // ── Accessors ─────────────────────────────

    public function getStatusEnumAttribute(): DamageStatus
    {
        return DamageStatus::from($this->status);
    }

    public function getDamageTypeEnumAttribute(): DamageType
    {
        return DamageType::from($this->damage_type);
    }
}
