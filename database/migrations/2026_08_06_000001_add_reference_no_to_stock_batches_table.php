<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a human-readable reference (e.g. "Sale #26887", "Purchase #PUR-...")
 * to stock_batches so OUT/IN records show the source document on the batches page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_batches', 'reference_no')) {
                $table->string('reference_no', 100)->nullable()->after('reference_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            if (Schema::hasColumn('stock_batches', 'reference_no')) {
                $table->dropColumn('reference_no');
            }
        });
    }
};
