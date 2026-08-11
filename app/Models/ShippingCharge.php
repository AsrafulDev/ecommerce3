<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCharge extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * District/area rows this shipping charge applies to.
     */
    public function districts()
    {
        return $this->belongsToMany(District::class, 'shipping_charge_district', 'shipping_charge_id', 'district_id');
    }
}
