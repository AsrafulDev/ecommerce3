<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to avoid doctrine/dbal dependency
        DB::statement('ALTER TABLE products MODIFY sold INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY subcategory_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE products MODIFY childcategory_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Revert if needed
    }
};
