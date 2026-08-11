<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = 'districts';

    protected $fillable = [
        'area_id',
        'area_name',
        'district',
        'charge_update_required',
    ];

    protected function casts(): array
    {
        return [
            'charge_update_required' => 'boolean',
        ];
    }

    /**
     * Shipping charges (zones) this district/area belongs to.
     */
    public function shippingCharges()
    {
        return $this->belongsToMany(ShippingCharge::class, 'shipping_charge_district', 'district_id', 'shipping_charge_id');
    }
}
