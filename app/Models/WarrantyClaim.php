<?php

namespace App\Models;

use App\Enums\WarrantyClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_sale_id',
        'customer_id',
        'order_id',
        'product_id',
        'claim_number',
        'issue_description',
        'issue_type',
        'attachments',
        'status',
        'resolution',
        'resolved_at',
        'rejection_reason',
        'servicing_cost',
        'store_bears_cost',
        'claimed_at',
        // Pipeline fields
        'product_received_at',
        'receive_challan_no',
        'receive_notes',
        'sent_to_supplier_at',
        'supplier_challan_no',
        'sent_supplier_id',
        'supplier_send_notes',
        'returned_from_supplier_at',
        'supplier_return_challan_no',
        'replacement_sn',
        'return_type',
        'supplier_return_notes',
        'ready_for_delivery_at',
        'delivery_challan_no',
        'delivered_to_customer_at',
        'delivery_notes',
        'supplier_charge',
        'customer_charge',
        // 🆕 Finance links + replacement stock reference
        'supplier_expense_id',
        'customer_earning_fund_id',
        'replacement_order_detail_id',
    ];

    protected function casts(): array
    {
        return [
            'attachments'            => 'array',
            'claimed_at'             => 'datetime',
            'resolved_at'            => 'datetime',
            'servicing_cost'         => 'decimal:2',
            'store_bears_cost'       => 'boolean',
            'product_received_at'    => 'datetime',
            'sent_to_supplier_at'    => 'datetime',
            'returned_from_supplier_at' => 'datetime',
            'ready_for_delivery_at'  => 'datetime',
            'delivered_to_customer_at' => 'datetime',
            'supplier_charge'        => 'decimal:2',
            'customer_charge'        => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────

    public function warrantySale()
    {
        return $this->belongsTo(WarrantySale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sentSupplier()
    {
        return $this->belongsTo(Supplier::class, 'sent_supplier_id');
    }

    public function stages()
    {
        return $this->hasMany(WarrantyClaimStage::class)->orderBy('started_at');
    }

    public function notes()
    {
        return $this->hasMany(WarrantyClaimNote::class)->latest();
    }

    public function challans()
    {
        return $this->hasMany(WarrantyChallan::class);
    }

    public function currentStage()
    {
        return $this->hasOne(WarrantyClaimStage::class)
                    ->whereNull('completed_at')
                    ->latest('started_at');
    }

    // ── 🆕 Reminders & Damage ─────────────────

    public function reminders()
    {
        return $this->hasMany(WarrantyClaimReminder::class);
    }

    public function damageProducts()
    {
        return $this->hasMany(DamageProduct::class);
    }

    public function supplierExpense()
    {
        return $this->belongsTo(Expense::class, 'supplier_expense_id');
    }

    public function customerEarningFund()
    {
        return $this->belongsTo(FundTransaction::class, 'customer_earning_fund_id');
    }

    public function replacementOrderDetail()
    {
        return $this->belongsTo(OrderDetails::class, 'replacement_order_detail_id');
    }

    /**
     * Get the active (pending) reminder for a given step, if any.
     */
    public function activeReminderFor(string $step): ?WarrantyClaimReminder
    {
        return $this->reminders()
            ->where('step', $step)
            ->where('status', 'pending')
            ->first();
    }

    // ── Helpers ───────────────────────────────

    public function transitionTo(WarrantyClaimStatus $newStatus, ?string $note = null): bool
    {
        $current = WarrantyClaimStatus::from($this->status);

        if (!$current->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$current->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus->value;

        if ($newStatus->isTerminal()) {
            $this->resolved_at = now();
        }

        $this->save();

        if ($note) {
            $this->notes()->create([
                'note'    => $note,
                'user_id' => auth()->id(),
            ]);
        }

        return true;
    }

    // ── Scopes ────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['resolved', 'rejected', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    // ── Accessors ─────────────────────────────

    public function getStatusEnumAttribute(): WarrantyClaimStatus
    {
        return WarrantyClaimStatus::from($this->status);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status_enum->isActive();
    }
}
