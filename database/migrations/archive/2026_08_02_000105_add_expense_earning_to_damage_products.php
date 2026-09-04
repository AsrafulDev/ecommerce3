<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_products', function (Blueprint $table) {
            if (!Schema::hasColumn('damage_products', 'expense_id')) {
                // Link back to the write-off expense (when status → unsellable)
                $table->foreignId('expense_id')->nullable()->after('resell_price')
                      ->constrained('expenses')->nullOnDelete();
            }
            if (!Schema::hasColumn('damage_products', 'earning_fund_id')) {
                // Link to the resale earning fund transaction (when status → resellable)
                $table->foreignId('earning_fund_id')->nullable()->after('expense_id')
                      ->constrained('fund_transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('damage_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('earning_fund_id');
            $table->dropConstrainedForeignId('expense_id');
        });
    }
};
