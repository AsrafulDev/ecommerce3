<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safely drop old unique on layout_id alone (if exists)
        // Use raw SQL since doctrine/dbal may not be installed
        $sm = DB::select("SHOW INDEX FROM homepage_layout_sections WHERE Key_name = 'homepage_layout_sections_layout_id_unique'");
        if (!empty($sm)) {
            DB::statement('ALTER TABLE homepage_layout_sections DROP INDEX homepage_layout_sections_layout_id_unique');
        }
        
        // Add correct composite unique (if not already exists)
        $existing = DB::select("SHOW INDEX FROM homepage_layout_sections WHERE Key_name = 'homepage_layout_sections_layout_section_unique'");
        if (empty($existing)) {
            DB::statement('ALTER TABLE homepage_layout_sections ADD UNIQUE homepage_layout_sections_layout_section_unique (layout_id, section_id)');
        }
    }

    public function down(): void
    {
        $existing = DB::select("SHOW INDEX FROM homepage_layout_sections WHERE Key_name = 'homepage_layout_sections_layout_section_unique'");
        if (!empty($existing)) {
            DB::statement('ALTER TABLE homepage_layout_sections DROP INDEX homepage_layout_sections_layout_section_unique');
        }
        
        $oldIndex = DB::select("SHOW INDEX FROM homepage_layout_sections WHERE Key_name = 'homepage_layout_sections_layout_id_unique'");
        if (empty($oldIndex)) {
            DB::statement('ALTER TABLE homepage_layout_sections ADD UNIQUE homepage_layout_sections_layout_id_unique (layout_id)');
        }
    }
};
