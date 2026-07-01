<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_purchase',
        'valid_from', 'valid_to', 'max_uses', 'used_count', 'status',
    ];

    protected function casts(): array
    {
        return [
            'value'        => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'valid_from'   => 'date',
            'valid_to'     => 'date',
            'max_uses'     => 'integer',
            'used_count'   => 'integer',
            'status'       => 'integer',
        ];
    }
}
