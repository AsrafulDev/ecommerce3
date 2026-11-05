<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Mushak 6.3 tax challan issued to a VAT-registered buyer for one sale. Written once at issue: the lines and the
 * party details are a snapshot, and only the status and void fields change afterwards.
 */
class VatChallan extends Model
{
    public const ISSUED = 'issued';

    public const VOIDED = 'voided';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
            'total_value' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_with_tax' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VatChallanLine::class, 'vat_challan_id')->orderBy('serial');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VatChallanEvent::class, 'vat_challan_id')->orderBy('id');
    }

    public function isIssued(): bool
    {
        return $this->status === self::ISSUED;
    }
}
