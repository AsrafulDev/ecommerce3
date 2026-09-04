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
            // NOTE: on fresh installs the create migration already defines all of
            // these columns, so Schema::hasColumn() is true and we must skip them
            // (the old "$add(...)->nullable()" pattern crashed with
            // "nullable() on null" on migrate:fresh). The guard still matters for
            // live DBs created with the older schema.
            $addIfMissing = [
                ['string', 'old_invoice_no', [50]],
                ['string', 'new_invoice_no', [50]],
                ['date', 'old_purchase_date', []],
                ['date', 'new_purchase_date', []],
                ['decimal', 'old_paid_amount', [15, 2]],
                ['decimal', 'new_paid_amount', [15, 2]],
                ['decimal', 'old_grand_total', [15, 2]],
                ['decimal', 'new_grand_total', [15, 2]],
                ['decimal', 'fund_balance_before', [15, 2]],
                ['decimal', 'fund_balance_after', [15, 2]],
                ['string', 'description', [500]],
                // users.id is INT UNSIGNED — unsignedInteger, no FK (errno 150 guard)
                ['unsignedInteger', 'performed_by', []],
            ];

            foreach ($addIfMissing as [$type, $col, $args]) {
                if (!Schema::hasColumn('purchase_logs', $col)) {
                    $table->{$type}($col, ...$args)->nullable();
                }
            }
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
