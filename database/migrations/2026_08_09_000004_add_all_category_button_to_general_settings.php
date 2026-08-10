<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All Category Button (on/off + presentation type) for Header & Footer Builder → Header Style
        $cols = [
            'header_all_category_button' => "ALTER TABLE general_settings ADD header_all_category_button TINYINT DEFAULT 1",
            'header_all_category_type'   => "ALTER TABLE general_settings ADD header_all_category_type VARCHAR(50) DEFAULT 'mega'",
        ];

        foreach ($cols as $col => $sql) {
            if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', $col)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
