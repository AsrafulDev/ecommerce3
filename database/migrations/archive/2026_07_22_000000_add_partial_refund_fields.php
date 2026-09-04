<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('refunds', 'refund_amount')) {
                $table->decimal('refund_amount', 14, 2)->nullable()->after('shipping_charge');
            }
            if (!Schema::hasColumn('refunds', 'include_shipping')) {
                $table->boolean('include_shipping')->default(true)->after('refund_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'include_shipping']);
        });
    }
};
