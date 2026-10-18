<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    public const KIND_GENERAL = 'general';
    public const KIND_EMPLOYEE = 'employee';
    public const KIND_EXPENSE_ADVANCE = 'expense_advance';
    public const KIND_SALARY_ADVANCE = 'salary_advance';
    public const TAKEN = 'taken';
    public const GIVEN = 'given';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'loan_date' => 'date',
        ];
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Principal still owed (taken) or still to be collected (given). */
    public function outstanding(): float
    {
        return round((float) $this->amount - (float) $this->repayments()->sum('amount'), 2);
    }
}
