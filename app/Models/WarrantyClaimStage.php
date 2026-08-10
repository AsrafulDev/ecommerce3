<?php

namespace App\Models;

use App\Enums\WarrantyStageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WarrantyClaimStage extends Model
{
    use HasFactory;

    // Cached per request so we don't re-check the table on every stage
    protected static ?bool $_hasAttachmentsTable = null;

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

    // Per-step attachments (images/PDF)
    public function attachments()
    {
        return $this->hasMany(WarrantyClaimStageAttachment::class);
    }

    /**
     * Safe accessor for the per-step attachments. Returns an empty collection
     * if the migration hasn't been run yet (table missing) instead of throwing
     * "Table 'warranty_claim_stage_attachments' doesn't exist".
     */
    public function attachmentsSafe()
    {
        if (self::$_hasAttachmentsTable === null) {
            self::$_hasAttachmentsTable = Schema::hasTable('warranty_claim_stage_attachments');
        }
        if (!self::$_hasAttachmentsTable) {
            return collect();
        }
        return $this->relationLoaded('attachments') ? $this->attachments : $this->attachments()->get();
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
