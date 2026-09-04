<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the sell-warranty record (`warranty_sales`) with the variant it
     * covers + terms/is_transferable (mirror of the purchase-warranty fields).
     * `stock_batch_id`/`purchase_id` already exist on this table.
     */
    public function up(): void
    {
        Schema::table('warranty_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('warranty_sales', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
                $table->index('variant_id');
            }
            if (!Schema::hasColumn('warranty_sales', 'terms')) {
                $table->text('terms')->nullable()->after('warranty_price');
            }
            if (!Schema::hasColumn('warranty_sales', 'is_transferable')) {
                $table->boolean('is_transferable')->default(true)->after('terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warranty_sales', function (Blueprint $table) {
            if (Schema::hasColumn('warranty_sales', 'variant_id')) {
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            }
            if (Schema::hasColumn('warranty_sales', 'terms')) {
                $table->dropColumn('terms');
            }
            if (Schema::hasColumn('warranty_sales', 'is_transferable')) {
                $table->dropColumn('is_transferable');
            }
        });
    }
};
