<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-product stock allocation method used by the storefront pricing engine
        // (FIFO = oldest first, LIFO = newest first, AVG = quantity-weighted average).
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'allocation_method')) {
                $table->string('allocation_method', 10)->default('FIFO')->after('costing_method')
                    ->comment('Storefront allocation: FIFO | LIFO | AVG (default FIFO)');
            }
        });

        // Whether a stock batch serves EVERY variant combination (ALL variants fallback)
        // instead of a single specific variant. False = batch is specific to
        // stock_batches.variant_price_id (or product-level when null).
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_batches', 'is_all_variants')) {
                $table->boolean('is_all_variants')->default(false)->after('variant_price_id')
                    ->comment('TRUE = batch applies to every variant combination (shared pool)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'allocation_method')) {
                $table->dropColumn('allocation_method');
            }
        });
        Schema::table('stock_batches', function (Blueprint $table) {
            if (Schema::hasColumn('stock_batches', 'is_all_variants')) {
                $table->dropColumn('is_all_variants');
            }
        });
    }
};
