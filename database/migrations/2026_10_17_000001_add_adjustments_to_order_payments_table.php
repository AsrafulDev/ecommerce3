<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount, VAT and AIT on an invoice reduce what is still due but move no cash.
 * They are recorded as order_payments rows with amount 0 and the reduction in
 * adjustment_amount, so the ledger keeps one row per event.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->decimal('adjustment_amount', 15, 2)->default(0)->after('amount');
            $table->string('adjustment_type', 20)->nullable()->after('adjustment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn(['adjustment_amount', 'adjustment_type']);
        });
    }
};
