<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `coupons` MODIFY `min_purchase` DECIMAL(14,2) NULL DEFAULT 0");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `coupons` MODIFY `min_purchase` DECIMAL(14,2) NOT NULL DEFAULT 0");
    }
};
