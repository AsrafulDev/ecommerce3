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
        Schema::create('homepage_layout_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layout_id');
            $table->unsignedBigInteger('section_id');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->string('columns_config', 50)->default('col-sm-12');
            $table->json('extra_settings')->nullable();
            $table->json('breakpoints')->nullable();
            $table->timestamps();

            $table->foreign('layout_id')->references('id')->on('homepage_layouts')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('homepage_sections')->onDelete('cascade');
            $table->unique(['layout_id', 'section_id', 'sort_order'], 'layout_section_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_layout_sections');
    }
};
