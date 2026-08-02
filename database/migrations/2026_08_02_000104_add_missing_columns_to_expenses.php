<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The expenses table was originally created without title/category/fund_transaction_id,
     * yet the Expense model + ExpenseController + views all rely on them. This migration
     * fills the gap so expense creation (incl. warranty supplier charges) works.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'title')) {
                $table->string('title', 255)->nullable()->after('id');
            }
            if (!Schema::hasColumn('expenses', 'category')) {
                $table->string('category', 100)->nullable()->after('title');
            }
            if (!Schema::hasColumn('expenses', 'fund_transaction_id')) {
                $table->bigInteger('fund_transaction_id')->unsigned()->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumnIfExists('fund_transaction_id');
            $table->dropColumnIfExists('category');
            $table->dropColumnIfExists('title');
        });
    }
};
