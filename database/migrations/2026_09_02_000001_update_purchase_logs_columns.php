<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: purchase_logs table was created with an older schema, but the
     * PurchaseLog model + PurchaseController::destroy() + purchases/logs view
     * expect these extra columns (old/new invoice_no, dates, paid/grand totals,
     * fund_balance_*, description, performed_by). Add them if missing so
     * PurchaseLog::create() no longer fails with "Unknown column
     * 'old_invoice_no'", which was 500-ing purchase deletion.
     *
     * Mirrors the earlier expense_logs fix (2026_08_03_000003/000004).
     */
    public function up(): void
    {
        Schema::table('purchase_logs', function (Blueprint $table) {
            $add = fn(string $type, string $col, ...$args) => !Schema::hasColumn('purchase_logs', $col)
                ? $table->{$type}($col, ...$args)
                : null;

            $add('string', 'old_invoice_no', 50)->nullable();
            $add('string', 'new_invoice_no', 50)->nullable();
            $add('date', 'old_purchase_date')->nullable();
            $add('date', 'new_purchase_date')->nullable();
            $add('decimal', 'old_paid_amount', 15, 2)->nullable();
            $add('decimal', 'new_paid_amount', 15, 2)->nullable();
            $add('decimal', 'old_grand_total', 15, 2)->nullable();
            $add('decimal', 'new_grand_total', 15, 2)->nullable();
            $add('decimal', 'fund_balance_before', 15, 2)->nullable();
            $add('decimal', 'fund_balance_after', 15, 2)->nullable();
            $add('string', 'description', 500)->nullable();
            // users.id is INT UNSIGNED — unsignedInteger, no FK (errno 150 guard)
            $add('unsignedInteger', 'performed_by')->nullable();
        });

        // Legacy NOT NULL balance_* columns aren't written by the code and
        // would otherwise block inserts ("Field 'balance_before' doesn't have
        // a default value") — make them nullable.
        Schema::table('purchase_logs', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_logs', 'balance_before')) {
                $table->decimal('balance_before', 15, 2)->nullable()->change();
            }
            if (Schema::hasColumn('purchase_logs', 'balance_after')) {
                $table->decimal('balance_after', 15, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_logs', function (Blueprint $table) {
            foreach (['old_invoice_no', 'new_invoice_no', 'old_purchase_date', 'new_purchase_date',
                     'old_paid_amount', 'new_paid_amount', 'old_grand_total', 'new_grand_total',
                     'fund_balance_before', 'fund_balance_after', 'description', 'performed_by'] as $col) {
                if (Schema::hasColumn('purchase_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('purchase_logs', 'balance_before')) {
                $table->decimal('balance_before', 15, 2)->change();
            }
            if (Schema::hasColumn('purchase_logs', 'balance_after')) {
                $table->decimal('balance_after', 15, 2)->change();
            }
        });
    }
};
