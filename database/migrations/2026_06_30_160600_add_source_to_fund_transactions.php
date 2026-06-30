<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('fund_transactions', 'source')) {
            DB::statement('ALTER TABLE fund_transactions ADD COLUMN source VARCHAR(50) NULL AFTER direction');
        }
        if (!Schema::hasColumn('fund_transactions', 'source_id')) {
            DB::statement('ALTER TABLE fund_transactions ADD COLUMN source_id BIGINT UNSIGNED NULL AFTER source');
        }
    }

    public function down(): void {}
};
