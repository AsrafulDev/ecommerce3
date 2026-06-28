<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add header_style and footer_style to general_settings
        $cols = [
            'header_style' => "ALTER TABLE general_settings ADD header_style VARCHAR(50) DEFAULT 'classic'",
            'footer_style' => "ALTER TABLE general_settings ADD footer_style VARCHAR(50) DEFAULT 'classic'",
            'header_top_bar' => "ALTER TABLE general_settings ADD header_top_bar TINYINT DEFAULT 1",
            'header_sticky' => "ALTER TABLE general_settings ADD header_sticky TINYINT DEFAULT 1",
            'footer_columns' => "ALTER TABLE general_settings ADD footer_columns INT DEFAULT 4",
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
