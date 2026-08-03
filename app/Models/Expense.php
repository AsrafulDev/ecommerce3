<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'amount',
        'expense_date',
        'category',
        'note',
        'fund_transaction_id',
        'created_by',
        'updated_by',
    ];

    public function fundTransaction()
    {
        return $this->belongsTo(FundTransaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Categories auto-created by the system (warranty flow) that are linked to
     * warranty claims / damage products. These must stay read-only to avoid
     * desyncing the linked records.
     */
    public const SYSTEM_CATEGORIES = ['warranty', 'warranty_repair', 'warranty_loss'];

    public function isSystemGenerated(): bool
    {
        return in_array($this->category, self::SYSTEM_CATEGORIES, true);
    }

    public function isEditable(): bool
    {
        return !$this->isSystemGenerated();
    }
}
