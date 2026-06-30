<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sms_gateways ADD COLUMN IF NOT EXISTS forget_pass TINYINT DEFAULT 0 AFTER status');
        DB::statement('ALTER TABLE sms_gateways ADD COLUMN IF NOT EXISTS order_confirm TINYINT DEFAULT 0 AFTER forget_pass');
        DB::statement('ALTER TABLE sms_gateways ADD COLUMN IF NOT EXISTS order_cancel TINYINT DEFAULT 0 AFTER order_confirm');
    }

    public function down(): void
    {
        // No rollback needed
    }
};
