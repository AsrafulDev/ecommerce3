<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('batch_variant_prices')) {
            Schema::create('batch_variant_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_batch_id');
                $table->unsignedBigInteger('variant_price_id');
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('old_price', 14, 2)->nullable();
                $table->unsignedInteger('stock')->default(0);
                $table->timestamps();

                $table->foreign('stock_batch_id')->references('id')->on('stock_batches')->cascadeOnDelete();
                $table->foreign('variant_price_id')->references('id')->on('product_variant_prices')->cascadeOnDelete();
                $table->unique(['stock_batch_id', 'variant_price_id']);
                $table->index('variant_price_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_variant_prices');
    }
};
