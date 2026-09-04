<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'forget_pass'  => 'TINYINT DEFAULT 0 AFTER status',
            'order_confirm' => 'TINYINT DEFAULT 0 AFTER forget_pass',
            'order_cancel'  => 'TINYINT DEFAULT 0 AFTER order_confirm',
            'order'         => 'TINYINT DEFAULT 0 AFTER order_cancel',
        ];

        foreach ($columns as $col => $def) {
            if (!Schema::hasColumn('sms_gateways', $col)) {
                DB::statement("ALTER TABLE sms_gateways ADD COLUMN `{$col}` {$def}");
            }
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
