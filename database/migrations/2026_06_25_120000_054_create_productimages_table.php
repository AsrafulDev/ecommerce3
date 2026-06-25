<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('productimages')) {
            Schema::create('productimages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('image', 255);
            $table->integer('product_id');
            $table->integer('color_id')->unsigned()->nullable();
            $table->integer('size_id')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('productimages');
    }
};
