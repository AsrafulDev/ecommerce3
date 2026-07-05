<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add whatsapp column if it doesn't exist
        if (Schema::hasTable('contacts') && !Schema::hasColumn('contacts', 'whatsapp')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->string('whatsapp', 50)->nullable()->after('maplink');
            });
        }

        // 2. Fix id column to be AUTO_INCREMENT if it's not already
        if (Schema::hasTable('contacts')) {
            $hasAutoIncrement = DB::selectOne(
                "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'contacts' 
                 AND COLUMN_NAME = 'id'",
                [DB::getDatabaseName()]
            );

            if ($hasAutoIncrement && $hasAutoIncrement->EXTRA !== 'auto_increment') {
                DB::statement('ALTER TABLE `contacts` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contacts') && Schema::hasColumn('contacts', 'whatsapp')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('whatsapp');
            });
        }
    }
};
