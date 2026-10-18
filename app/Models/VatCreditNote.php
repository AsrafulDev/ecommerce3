<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VatCreditNote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'taxable_value' => 'decimal:2',
            'vat_amount' => 'decimal:2',
        ];
    }
}
