<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'refund_account_name' => 'VARCHAR(100) NULL AFTER refund_account',
            'refund_method'       => 'VARCHAR(50) NULL AFTER reason',
            'refund_account'      => 'VARCHAR(100) NULL AFTER refund_method',
            'processed_by'        => 'BIGINT UNSIGNED NULL AFTER status',
            'processed_at'        => 'TIMESTAMP NULL AFTER processed_by',
            'admin_note'          => 'TEXT NULL AFTER reason',
        ];

        foreach ($columns as $col => $def) {
            if (!Schema::hasColumn('refunds', $col)) {
                DB::statement("ALTER TABLE refunds ADD COLUMN `{$col}` {$def}");
            }
        }
    }

    public function down(): void
    {
        // No rollback
    }
};
