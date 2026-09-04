<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `total_cost` = quantity × unit_cost for the batch (stored snapshot;
     * kept in sync by purchase/return/adjustment write paths).
     */
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_batches', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->default(0)->after('unit_cost')
                    ->comment('Total cost for this batch (quantity x unit_cost)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (Schema::hasColumn('stock_batches', 'total_cost')) {
                $table->dropColumn('total_cost');
            }
        });
    }
};
