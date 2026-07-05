<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'invoice_no')) {
                    $table->string('invoice_no', 50)->nullable()->after('supplier_id');
                }
                if (!Schema::hasColumn('purchases', 'total_qty')) {
                    $table->integer('total_qty')->default(0)->after('purchase_date');
                }
                if (!Schema::hasColumn('purchases', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0)->after('total_qty');
                }
                if (!Schema::hasColumn('purchases', 'discount')) {
                    $table->decimal('discount', 15, 2)->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn('purchases', 'shipping_cost')) {
                    $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount');
                }
                if (!Schema::hasColumn('purchases', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0)->after('grand_total');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn([
                    'invoice_no', 'total_qty', 'subtotal',
                    'discount', 'shipping_cost', 'paid_amount',
                ]);
            });
        }
    }
};
