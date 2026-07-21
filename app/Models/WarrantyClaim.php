<?php

namespace App\Models;

use App\Enums\WarrantyClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_sale_id',
        'customer_id',
        'order_id',
        'product_id',
        'claim_number',
        'issue_description',
        'issue_type',
        'attachments',
        'status',
        'resolution',
        'resolved_at',
        'rejection_reason',
        'servicing_cost',
        'store_bears_cost',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments'      => 'array',
            'claimed_at'       => 'datetime',
            'resolved_at'      => 'datetime',
            'servicing_cost'   => 'decimal:2',
            'store_bears_cost' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────

    public function warrantySale()
    {
        return $this->belongsTo(WarrantySale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stages()
    {
        return $this->hasMany(WarrantyClaimStage::class)->orderBy('started_at');
    }

    public function notes()
    {
        return $this->hasMany(WarrantyClaimNote::class)->latest();
    }

    public function currentStage()
    {
        return $this->hasOne(WarrantyClaimStage::class)
                    ->whereNull('completed_at')
                    ->latest('started_at');
    }

    // ── Helpers ───────────────────────────────

    public function transitionTo(WarrantyClaimStatus $newStatus, ?string $note = null): bool
    {
        $current = WarrantyClaimStatus::from($this->status);

        if (!$current->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$current->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus->value;

        if ($newStatus->isTerminal()) {
            $this->resolved_at = now();
        }

        $this->save();

        if ($note) {
            $this->notes()->create([
                'note'    => $note,
                'user_id' => auth()->id(),
            ]);
        }

        return true;
    }

    // ── Scopes ────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['resolved', 'rejected', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    // ── Accessors ─────────────────────────────

    public function getStatusEnumAttribute(): WarrantyClaimStatus
    {
        return WarrantyClaimStatus::from($this->status);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status_enum->isActive();
    }
}
