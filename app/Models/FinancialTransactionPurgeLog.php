<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransactionPurgeLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_date' => 'datetime',
            'performed_at' => 'datetime',
            'dependency_snapshot' => 'array',
            'before_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }
}
