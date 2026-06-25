<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcategories')) {
            Schema::create('subcategories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subcategoryName', 255);
            $table->string('slug', 255);
            $table->integer('category_id');
            $table->text('image')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_decription')->nullable();
            $table->tinyInteger('status');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategories');
    }
};
