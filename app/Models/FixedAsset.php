<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    public const ACTIVE = 'active';
    public const DISPOSED = 'disposed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'revalued_amount' => 'decimal:2',
            'disposal_value' => 'decimal:2',
            'purchase_date' => 'date',
            'disposal_date' => 'date',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(FixedAssetEvent::class);
    }

    /** Carrying value: the revalued amount if one was recorded, otherwise cost less depreciation. */
    public function bookValue(): float
    {
        if ($this->revalued_amount !== null) {
            return round((float) $this->revalued_amount, 2);
        }

        return round((float) $this->cost - (float) $this->accumulated_depreciation, 2);
    }
}
