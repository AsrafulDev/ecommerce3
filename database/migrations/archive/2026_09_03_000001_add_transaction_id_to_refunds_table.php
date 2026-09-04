<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: RefundController::process() saves $refund->transaction_id, but no
     * migration ever created that column — processing a refund 500'd with
     * "Unknown column 'transaction_id'". Add it guarded so fresh + live DBs both
     * get it. (Surfaced while verifying UPDATE-PLAN Phase 2.1.)
     */
    public function up(): void
    {
        if (!Schema::hasColumn('refunds', 'transaction_id')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->string('transaction_id', 255)->nullable()->after('refund_account');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('refunds', 'transaction_id')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->dropColumn('transaction_id');
            });
        }
    }
};
