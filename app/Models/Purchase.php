<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id','invoice_no','purchase_date',
        'total_qty','subtotal','discount','shipping_cost',
        'grand_total','paid_amount','due_amount',
        'note','status','draft_data','created_by','updated_by',
    ];

    protected $dates = ['purchase_date'];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'draft_data' => 'array',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function supplierWarranties()
    {
        return $this->hasManyThrough(
            SupplierWarranty::class,
            PurchaseItem::class,
            'purchase_id',
            'purchase_item_id'
        );
    }
}
