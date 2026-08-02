<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('orders', 'due_amount')) {
                $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            }
        });

        // Backfill: paid/completed orders → paid_amount = amount, due = 0
        DB::table('orders')
            ->whereIn('payment_status', ['paid', 'completed', 'success', 'approved'])
            ->update(['paid_amount' => DB::raw('amount'), 'due_amount' => 0]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumnIfExists('due_amount');
            $table->dropColumnIfExists('paid_amount');
        });
    }
};
