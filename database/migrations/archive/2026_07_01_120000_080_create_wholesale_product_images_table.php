<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wholesale_product_images')) {
            Schema::create('wholesale_product_images', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('wholesale_product_id')->unsigned();
                $table->string('image', 255);
                $table->integer('sort_order')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('wholesale_product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_product_images');
    }
};
