<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commerce feature flags per batch (batch-scoped source of truth):
     *  - has_purchase_warranty : batch carries a supplier/purchase warranty
     *  - has_sell_warranty     : batch is sold with a customer (sell) warranty
     *  - has_wholesale         : batch has wholesale tiers / conditions
     */
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_batches', 'has_purchase_warranty')) {
                $table->boolean('has_purchase_warranty')->default(false)->after('pos_enabled');
            }
            if (!Schema::hasColumn('stock_batches', 'has_sell_warranty')) {
                $table->boolean('has_sell_warranty')->default(false)->after('has_purchase_warranty');
            }
            if (!Schema::hasColumn('stock_batches', 'has_wholesale')) {
                $table->boolean('has_wholesale')->default(false)->after('has_sell_warranty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('stock_batches', 'has_purchase_warranty') ? 'has_purchase_warranty' : null,
                Schema::hasColumn('stock_batches', 'has_sell_warranty') ? 'has_sell_warranty' : null,
                Schema::hasColumn('stock_batches', 'has_wholesale') ? 'has_wholesale' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
