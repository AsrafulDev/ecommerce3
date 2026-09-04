<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_claims', function (Blueprint $table) {
            // Expense created from supplier_charge
            if (!Schema::hasColumn('warranty_claims', 'supplier_expense_id')) {
                $table->foreignId('supplier_expense_id')->nullable()->after('supplier_charge')
                      ->constrained('expenses')->nullOnDelete();
            }
            // Fund "in" transaction created from customer_charge (earning)
            if (!Schema::hasColumn('warranty_claims', 'customer_earning_fund_id')) {
                $table->foreignId('customer_earning_fund_id')->nullable()->after('customer_charge')
                      ->constrained('fund_transactions')->nullOnDelete();
            }
            // Instant replacement stock reference (order_details.id is INT UNSIGNED)
            if (!Schema::hasColumn('warranty_claims', 'replacement_order_detail_id')) {
                $table->unsignedInteger('replacement_order_detail_id')->nullable()->after('replacement_sn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warranty_claims', function (Blueprint $table) {
            $table->dropColumnIfExists('replacement_order_detail_id');
            $table->dropConstrainedForeignId('supplier_expense_id');
            $table->dropConstrainedForeignId('customer_earning_fund_id');
        });
    }
};
