<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: expense_logs table was created with an older schema, but
     * ExpenseLog model + ExpenseController expect these extra columns
     * (old_title, new_title, dates, categories, fund_balance_*, description,
     * performed_by). Add them if missing so ExpenseLog::create() no longer fails
     * with "Unknown column 'old_title'".
     */
    public function up(): void
    {
        Schema::table('expense_logs', function (Blueprint $table) {
            $add = fn(string $type, string $col, ...$args) => !Schema::hasColumn('expense_logs', $col)
                ? $table->{$type}($col, ...$args)
                : null;

            $add('string', 'old_title', 255)->nullable();
            $add('string', 'new_title', 255)->nullable();
            $add('date', 'old_expense_date')->nullable();
            $add('date', 'new_expense_date')->nullable();
            $add('string', 'old_category', 100)->nullable();
            $add('string', 'new_category', 100)->nullable();
            $add('decimal', 'fund_balance_before', 15, 2)->nullable();
            $add('decimal', 'fund_balance_after', 15, 2)->nullable();
            $add('string', 'description', 500)->nullable();
            // users.id is INT UNSIGNED — unsignedInteger, no FK (errno 150 guard)
            $add('unsignedInteger', 'performed_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expense_logs', function (Blueprint $table) {
            foreach (['old_title','new_title','old_expense_date','new_expense_date',
                     'old_category','new_category','fund_balance_before','fund_balance_after',
                     'description','performed_by'] as $col) {
                if (Schema::hasColumn('expense_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
