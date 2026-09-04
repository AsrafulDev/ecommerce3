<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `complaints` MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `complaints` MODIFY `status` TINYINT NOT NULL DEFAULT 1");
    }
};
