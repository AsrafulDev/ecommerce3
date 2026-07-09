<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    protected $fillable = [
        'supplier_id', 'purchase_id', 'return_no', 'return_date',
        'total_qty', 'total_amount', 'reason', 'status', 'created_by'
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'total_qty' => 'integer',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(SupplierReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
