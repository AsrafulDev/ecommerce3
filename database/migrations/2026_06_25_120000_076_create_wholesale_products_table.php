<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wholesale_products')) {
            Schema::create('wholesale_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('vendor_id')->unsigned()->nullable();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->bigInteger('category_id')->unsigned();
            $table->decimal('price', 14,2);
            $table->integer('stock')->default(0);
            $table->text('description')->nullable();
            $table->string('feature_image', 255)->nullable();
            $table->tinyInteger('feature_product')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_products');
    }
};
