<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add SN tracking columns to stock_batches
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_batches', 'sn_stock')) {
                $table->json('sn_stock')->nullable()->after('remaining_qty')
                    ->comment('Serial numbers available in this batch');
            }
            if (!Schema::hasColumn('stock_batches', 'sn_sold')) {
                $table->json('sn_sold')->nullable()->after('sn_stock')
                    ->comment('Serial numbers sold/assigned from this batch');
            }
        });

        // Add is_sn_required to products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_sn_required')) {
                $table->boolean('is_sn_required')->default(false)->after('is_wholesale')
                    ->comment('Require serial number entry per unit when selling');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropColumn(['sn_stock', 'sn_sold']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_sn_required');
        });
    }
};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            //
        });
    }
};
