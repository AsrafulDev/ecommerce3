<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VatChallanLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_value' => 'decimal:4',
            'line_value' => 'decimal:2',
            'sd_amount' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
