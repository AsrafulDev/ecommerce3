<?php

namespace App\Models;

use App\Enums\WarrantyStageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaimStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'stage',
        'status',
        'notes',
        'handled_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────

    public function warrantyClaim()
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // ── Helpers ───────────────────────────────

    public function complete(?string $notes = null): void
    {
        $this->update([
            'status'       => 'completed',
            'notes'        => $notes ?? $this->notes,
            'completed_at' => now(),
        ]);
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getStageEnumAttribute(): WarrantyStageType
    {
        return WarrantyStageType::from($this->stage);
    }
}
