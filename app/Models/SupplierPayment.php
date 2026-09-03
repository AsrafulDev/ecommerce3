<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id','purchase_id','amount',
        'payment_date','method','note',
        'fund_transaction_id','created_by',
    ];

    protected $dates = ['payment_date'];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
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

    public function fundTransaction()
    {
        return $this->belongsTo(\App\Models\FundTransaction::class);
    }

    protected static function booted()
    {
        static::created(function (SupplierPayment $payment) {
            if ($payment->supplier_id && $payment->amount) {
                \App\Models\Supplier::where('id', $payment->supplier_id)
                    ->increment('total_paid', (float) $payment->amount);
            }
        });

        static::deleted(function (SupplierPayment $payment) {
            if ($payment->supplier_id && $payment->amount) {
                \App\Models\Supplier::where('id', $payment->supplier_id)
                    ->decrement('total_paid', (float) $payment->amount);
            }
        });
    }
}
