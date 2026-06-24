<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('sku', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_prices');
    }
};
