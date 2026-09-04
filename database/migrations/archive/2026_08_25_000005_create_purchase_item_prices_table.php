<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_item_prices')) {
            Schema::create('purchase_item_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_item_id');
                $table->unsignedBigInteger('variant_price_id')->nullable();
                $table->decimal('selling_price', 14, 2);
                $table->decimal('mrp', 14, 2)->nullable();
                $table->decimal('wholesale_price', 14, 2)->nullable();
                $table->json('wholesale_tiers')->nullable();
                $table->json('warranty_tiers')->nullable();
                $table->timestamps();

                $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->cascadeOnDelete();
                $table->index('variant_price_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_item_prices');
    }
};
