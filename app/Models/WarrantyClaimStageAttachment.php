<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaimStageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_stage_id',
        'file_path',
        'file_name',
        'file_type',
        'uploaded_by',
    ];

    // ── Relationships ─────────────────────────

    public function stage()
    {
        return $this->belongsTo(WarrantyClaimStage::class, 'warranty_claim_stage_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
