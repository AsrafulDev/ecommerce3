<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_layout_sections', function (Blueprint $table) {
            // Safely drop old unique on layout_id alone (if exists)
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('homepage_layout_sections');
            
            if (array_key_exists('homepage_layout_sections_layout_id_unique', $indexes)) {
                $table->dropUnique('homepage_layout_sections_layout_id_unique');
            }
            
            // Add correct composite unique (if not already exists)
            if (!array_key_exists('homepage_layout_sections_layout_section_unique', $indexes)) {
                $table->unique(['layout_id', 'section_id'], 'homepage_layout_sections_layout_section_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('homepage_layout_sections', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('homepage_layout_sections');
            
            if (array_key_exists('homepage_layout_sections_layout_section_unique', $indexes)) {
                $table->dropUnique('homepage_layout_sections_layout_section_unique');
            }
            
            if (!array_key_exists('homepage_layout_sections_layout_id_unique', $indexes)) {
                $table->unique('layout_id');
            }
        });
    }
};
