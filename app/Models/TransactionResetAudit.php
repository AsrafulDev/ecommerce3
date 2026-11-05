<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row per sales reset action. Rows are only ever inserted; there is no update or delete path. */
class TransactionResetAudit extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'reversal_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
