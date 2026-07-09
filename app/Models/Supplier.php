<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name','phone','email','address',
        'opening_balance','current_due',
        'company','contact_person','tax_id','payment_terms',
        'lead_time','notes','is_active',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
