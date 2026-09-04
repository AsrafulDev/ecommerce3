<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_products', function (Blueprint $table) {
            if (!Schema::hasColumn('damage_products', 'supplier_id')) {
                // Supplier the damaged unit was sent to for warranty claim
                $table->foreignId('supplier_id')->nullable()->after('product_id')
                      ->constrained('suppliers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('damage_products', function (Blueprint $table) {
            if (Schema::hasColumn('damage_products', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }
        });
    }
};