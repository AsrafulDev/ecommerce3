<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('childcategories')) {
            Schema::create('childcategories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('childcategoryName', 255)->default('text');
            $table->string('slug', 255)->default('text');
            $table->integer('subcategory_id')->unsigned()->default(0);
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
        Schema::dropIfExists('childcategories');
    }
};
