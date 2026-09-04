<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The controller/model write fund_balance_before/fund_balance_after, but the
     * legacy balance_before/balance_after columns are NOT NULL with no default —
     * so inserts fail with "Field 'balance_before' doesn't have a default value".
     * Make the legacy columns nullable.
     */
    public function up(): void
    {
        Schema::table('expense_logs', function (Blueprint $table) {
            if (Schema::hasColumn('expense_logs', 'balance_before')) {
                $table->decimal('balance_before', 15, 2)->nullable()->change();
            }
            if (Schema::hasColumn('expense_logs', 'balance_after')) {
                $table->decimal('balance_after', 15, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_logs', function (Blueprint $table) {
            if (Schema::hasColumn('expense_logs', 'balance_before')) {
                $table->decimal('balance_before', 15, 2)->change();
            }
            if (Schema::hasColumn('expense_logs', 'balance_after')) {
                $table->decimal('balance_after', 15, 2)->change();
            }
        });
    }
};
