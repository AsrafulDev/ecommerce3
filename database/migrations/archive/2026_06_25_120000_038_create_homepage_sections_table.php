<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homepage_sections')) {
            Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('preview_image', 255)->nullable();
            $table->tinyInteger('is_system')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->longText('settings_schema')->nullable();
            $table->string('default_columns', 20)->default('col-sm-12');
            $table->integer('default_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
