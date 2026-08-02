<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaimReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'step',
        'label',
        'remind_at',
        'status',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────

    public function warrantyClaim()
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', 'done');
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->pending()->whereDate('remind_at', now()->today());
    }

    public function scopeDueTomorrow(Builder $query): Builder
    {
        return $query->pending()->whereDate('remind_at', now()->addDay());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()->whereDate('remind_at', '<', now()->today());
    }

    // ── Accessors ─────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->remind_at?->lt(now()->today());
    }
}
