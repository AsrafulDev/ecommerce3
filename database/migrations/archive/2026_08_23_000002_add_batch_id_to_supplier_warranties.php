<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_warranties', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_warranties', 'batch_id')) {
                // Optional link to the stock batch this warranty applies to
                $table->foreignId('batch_id')->nullable()->after('purchase_item_id')
                      ->constrained('stock_batches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_warranties', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_warranties', 'batch_id')) {
                $table->dropConstrainedForeignId('batch_id');
            }
        });
    }
};