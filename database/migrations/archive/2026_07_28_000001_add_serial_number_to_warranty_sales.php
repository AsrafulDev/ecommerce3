<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('warranty_sales', 'serial_number')) {
                $table->string('serial_number', 100)->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('warranty_sales', 'sold_by')) {
                $table->unsignedInteger('sold_by')->nullable()->after('customer_id');
                $table->foreign('sold_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('warranty_sales', 'stock_batch_id')) {
                $table->unsignedBigInteger('stock_batch_id')->nullable()->after('supplier_warranty_id');
                $table->foreign('stock_batch_id')->references('id')->on('stock_batches')->onDelete('set null');
            }
            if (!Schema::hasColumn('warranty_sales', 'purchase_id')) {
                $table->unsignedBigInteger('purchase_id')->nullable()->after('stock_batch_id');
                $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warranty_sales', function (Blueprint $table) {
            $columns = ['serial_number', 'sold_by', 'stock_batch_id', 'purchase_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('warranty_sales', $col)) {
                    if ($col === 'sold_by') {
                        $table->dropForeign('warranty_sales_sold_by_foreign');
                    } elseif ($col === 'stock_batch_id') {
                        $table->dropForeign('warranty_sales_stock_batch_id_foreign');
                    } elseif ($col === 'purchase_id') {
                        $table->dropForeign('warranty_sales_purchase_id_foreign');
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
