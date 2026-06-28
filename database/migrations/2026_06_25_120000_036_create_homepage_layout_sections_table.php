<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homepage_layout_sections')) {
            Schema::create('homepage_layout_sections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('layout_id')->unsigned();
            $table->bigInteger('section_id')->unsigned();
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('is_visible')->default(1);
            $table->string('columns_config', 50)->default('col-sm-12');
            $table->longText('extra_settings')->nullable();
            $table->longText('breakpoints')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['layout_id', 'section_id'], 'homepage_layout_sections_layout_section_unique');
            $table->index('section_id');
            // CONSTRAINT `homepage_layout_sections_layout_id_foreign` FOREIGN KEY (`layout_id`) REFERENCES `homepage_layouts` (`id`) ON DELETE CASCADE
            // CONSTRAINT `homepage_layout_sections_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `homepage_sections` (`id`) ON DELETE CASCADE
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_layout_sections');
    }
};
