<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'refund_id',
        'amount',
        'shipping_charge',
        'refund_amount',
        'include_shipping',
        'reason',
        'admin_note',
        'customer_note',
        'status',
        'refund_method',
        'refund_account',
        'refund_account_name',
        'transaction_id',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'include_shipping' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Get the effective total refund amount.
     * Uses refund_amount if set (partial refund), otherwise uses amount + optional shipping.
     */
    public function totalRefundAmount(): float
    {
        if ($this->refund_amount !== null) {
            return (float) $this->refund_amount;
        }
        $total = (float) $this->amount;
        if ($this->include_shipping) {
            $total += (float) $this->shipping_charge;
        }
        return $total;
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    // Generate unique refund ID
    public static function generateRefundId()
    {
        do {
            $refundId = 'REF-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('refund_id', $refundId)->exists());

        return $refundId;
    }
}
