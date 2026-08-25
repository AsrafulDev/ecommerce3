<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('batch_warranty_tiers')) {
            Schema::create('batch_warranty_tiers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_batch_id');
                $table->unsignedBigInteger('variant_price_id')->nullable();
                $table->unsignedBigInteger('warranty_tier_id');
                $table->decimal('additional_cost', 14, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('stock_batch_id')->references('id')->on('stock_batches')->cascadeOnDelete();
                $table->foreign('variant_price_id')->references('id')->on('product_variant_prices')->cascadeOnDelete();
                $table->foreign('warranty_tier_id')->references('id')->on('product_warranty_tiers')->cascadeOnDelete();
                $table->unique(['stock_batch_id', 'variant_price_id', 'warranty_tier_id'], 'batch_warranty_unique');
                $table->index('warranty_tier_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_warranty_tiers');
    }
};
