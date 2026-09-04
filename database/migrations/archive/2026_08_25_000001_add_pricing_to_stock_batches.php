<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_batches', 'mrp')) {
                $table->decimal('mrp', 14, 2)->nullable()->after('selling_price')
                    ->comment('Compare-at / strike-through price for this batch');
            }
            if (!Schema::hasColumn('stock_batches', 'wholesale_price')) {
                $table->decimal('wholesale_price', 14, 2)->nullable()->after('mrp')
                    ->comment('Quick single wholesale price for this batch');
            }
            if (!Schema::hasColumn('stock_batches', 'is_active_for_website')) {
                $table->boolean('is_active_for_website')->default(false)->after('exp_date')
                    ->comment('One per product — the batch the website shows & prices from');
            }
            if (!Schema::hasColumn('stock_batches', 'pos_enabled')) {
                $table->boolean('pos_enabled')->default(true)->after('is_active_for_website')
                    ->comment('Whether this batch is selectable in POS');
            }
            if (!Schema::hasColumn('stock_batches', 'auto_advance')) {
                $table->boolean('auto_advance')->default(true)->after('pos_enabled')
                    ->comment('Auto-activate next FIFO batch when this one sells out');
            }
            if (!Schema::hasColumn('stock_batches', 'is_manual_price')) {
                $table->boolean('is_manual_price')->default(false)->after('auto_advance')
                    ->comment('Price set manually vs inherited from product');
            }
            if (!Schema::hasColumn('stock_batches', 'price_updated_at')) {
                $table->timestamp('price_updated_at')->nullable()->after('is_manual_price');
            }
            if (!Schema::hasColumn('stock_batches', 'price_updated_by')) {
                $table->unsignedBigInteger('price_updated_by')->nullable()->after('price_updated_at');
            }

            // NOTE: The "one active website batch per product" rule is NOT enforced
            // with a (partial) unique index — MySQL 5.7 / < 8.0.13 cannot build a
            // filtered index, and a plain UNIQUE(product_id) would break because a
            // product has many batches. The invariant is enforced in the app layer:
            //   PricingService::setActiveWebsiteBatch()  (transaction: clears all,
            //   sets one) and PricingService::advanceActiveBatchIfDepleted().
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            try {
                if (Schema::hasIndex('stock_batches', 'stock_batches_one_active_website')) {
                    $table->dropUnique('stock_batches_one_active_website');
                }
            } catch (\Throwable $e) {
                // index never existed (MySQL 5.7 path) — ignore
            }

            $table->dropColumn([
                'mrp', 'wholesale_price', 'is_active_for_website', 'pos_enabled',
                'auto_advance', 'is_manual_price', 'price_updated_at', 'price_updated_by',
            ]);
        });
    }
};
