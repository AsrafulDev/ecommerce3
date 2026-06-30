<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE fund_transactions ADD COLUMN IF NOT EXISTS source VARCHAR(50) NULL AFTER direction');
        DB::statement('ALTER TABLE fund_transactions ADD COLUMN IF NOT EXISTS source_id BIGINT UNSIGNED NULL AFTER source');
    }

    public function down(): void {}
};
