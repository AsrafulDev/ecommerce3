<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('batch_wholesale_prices')) {
            Schema::create('batch_wholesale_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_batch_id');
                $table->unsignedBigInteger('variant_price_id')->nullable();
                $table->unsignedInteger('min_quantity');
                $table->unsignedInteger('max_quantity')->nullable();
                $table->decimal('wholesale_price', 14, 2);
                $table->timestamps();

                $table->foreign('stock_batch_id')->references('id')->on('stock_batches')->cascadeOnDelete();
                $table->foreign('variant_price_id')->references('id')->on('product_variant_prices')->cascadeOnDelete();
                $table->index(['stock_batch_id', 'variant_price_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_wholesale_prices');
    }
};
