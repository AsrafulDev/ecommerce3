<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_payments') || Schema::hasColumn('order_payments', 'fund_transaction_id')) {
            return;
        }

        Schema::table('order_payments', function (Blueprint $table): void {
            // Nullable by design: historical payment rows predate deterministic
            // fund tracing and must not be fabricated during this phase.
            $table->unsignedBigInteger('fund_transaction_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('order_payments') && Schema::hasColumn('order_payments', 'fund_transaction_id')) {
            Schema::table('order_payments', function (Blueprint $table): void {
                $table->dropColumn('fund_transaction_id');
            });
        }
    }
};
