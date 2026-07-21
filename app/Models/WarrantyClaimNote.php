<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaimNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'user_id',
        'note',
        'attachment',
    ];

    // ── Relationships ─────────────────────────

    public function warrantyClaim()
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
