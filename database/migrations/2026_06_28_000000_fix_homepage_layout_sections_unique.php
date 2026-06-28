<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_layout_sections', function (Blueprint $table) {
            // Drop the wrong unique constraint on layout_id alone
            $table->dropUnique('homepage_layout_sections_layout_id_unique');
            
            // Add correct composite unique: one section per layout (no duplicate sections in same layout)
            $table->unique(['layout_id', 'section_id'], 'homepage_layout_sections_layout_section_unique');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_layout_sections', function (Blueprint $table) {
            $table->dropUnique('homepage_layout_sections_layout_section_unique');
            $table->unique('layout_id');
        });
    }
};
