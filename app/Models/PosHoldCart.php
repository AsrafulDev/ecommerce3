<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosHoldCart extends Model
{
    protected $fillable = [
        'customer_id', 'customer_name', 'customer_phone',
        'cart_data', 'subtotal', 'discount', 'shipping_charge',
        'grand_total', 'note', 'held_by', 'held_at', 'restored_at', 'status'
    ];

    protected function casts(): array
    {
        return [
            'cart_data' => 'json',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'held_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function heldBy()
    {
        return $this->belongsTo(User::class, 'held_by');
    }
}
