<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE refunds ADD COLUMN IF NOT EXISTS refund_account_name VARCHAR(100) NULL AFTER refund_account');
        DB::statement('ALTER TABLE refunds ADD COLUMN IF NOT EXISTS refund_method VARCHAR(50) NULL AFTER reason');
        DB::statement('ALTER TABLE refunds ADD COLUMN IF NOT EXISTS refund_account VARCHAR(100) NULL AFTER refund_method');
        DB::statement('ALTER TABLE refunds ADD COLUMN IF NOT EXISTS processed_by BIGINT UNSIGNED NULL AFTER status');
        DB::statement('ALTER TABLE refunds ADD COLUMN IF NOT EXISTS processed_at TIMESTAMP NULL AFTER processed_by');
        DB::statement('ALTER TABLE refunds ADD COLUMN IF NOT EXISTS admin_note TEXT NULL AFTER reason');
    }

    public function down(): void
    {
        // No rollback
    }
};
