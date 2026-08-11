<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shipping fee now lives on ShippingCharge (zone + amount). Remove the old
     * per-area shippingfee and partialpayment columns from the districts table.
     */
    public function up(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            if (Schema::hasColumn('districts', 'shippingfee')) {
                $table->dropColumn('shippingfee');
            }
            if (Schema::hasColumn('districts', 'partialpayment')) {
                $table->dropColumn('partialpayment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            if (!Schema::hasColumn('districts', 'shippingfee')) {
                $table->string('shippingfee', 255)->nullable();
            }
            if (!Schema::hasColumn('districts', 'partialpayment')) {
                $table->string('partialpayment', 255)->nullable();
            }
        });
    }
};
