<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('preview_image', 255)->nullable();

            // Color Palette
            $table->string('primary_color', 7)->default('#0d6efd');
            $table->string('secondary_color', 7)->default('#198754');
            $table->string('accent_color', 7)->default('#ff6a00');
            $table->string('text_color', 7)->default('#212529');
            $table->string('heading_color', 7)->default('#111111');
            $table->string('body_bg_color', 7)->default('#ffffff');
            $table->string('header_bg_color', 7)->default('#ffffff');
            $table->string('header_text_color', 7)->default('#212529');
            $table->string('footer_bg_color', 7)->default('#1a1a1a');
            $table->string('footer_text_color', 7)->default('#cccccc');
            $table->string('copyright_bg_color', 7)->default('#000000');
            $table->string('copyright_text_color', 7)->default('#ffffff');
            $table->string('button_bg_color', 7)->default('#0d6efd');
            $table->string('button_text_color', 7)->default('#ffffff');
            $table->string('button_hover_bg_color', 7)->default('#0b5ed7');
            $table->string('border_color', 7)->default('#dee2e6');
            $table->string('sale_badge_bg', 7)->default('#dc3545');
            $table->string('sale_badge_text', 7)->default('#ffffff');

            // Typography
            $table->string('font_family', 100)->default("'Roboto', sans-serif");
            $table->string('heading_font', 100)->default("'Jost', sans-serif");
            $table->string('body_font_size', 10)->default('14px');
            $table->string('heading_font_weight', 10)->default('700');

            // Layout
            $table->enum('layout_style', ['full-width', 'boxed', 'contained'])->default('contained');
            $table->string('border_radius', 10)->default('8px');
            $table->string('card_shadow', 100)->default('0 2px 8px rgba(0,0,0,0.08)');

            // Custom CSS
            $table->text('custom_css')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
