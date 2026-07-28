<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyChallan extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'challan_type',
        'challan_no',
        'challan_data',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'challan_data' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────

    public function warrantyClaim()
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // ── Accessors ─────────────────────────────

    public function getChallanTypeLabelAttribute(): string
    {
        return match ($this->challan_type) {
            'receive'        => 'Product Receive',
            'send_to_supplier'=> 'Sent to Supplier',
            'receive_return'  => 'Supplier Return',
            'delivery'        => 'Customer Delivery',
            default           => $this->challan_type,
        };
    }
}
