<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_sales', function (Blueprint $table) {
            // Drop old single-SN column
            if (Schema::hasColumn('warranty_sales', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
            // Add JSON column for multiple SNs
            if (!Schema::hasColumn('warranty_sales', 'serial_numbers')) {
                $table->json('serial_numbers')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warranty_sales', function (Blueprint $table) {
            if (Schema::hasColumn('warranty_sales', 'serial_numbers')) {
                $table->dropColumn('serial_numbers');
            }
            if (!Schema::hasColumn('warranty_sales', 'serial_number')) {
                $table->string('serial_number', 100)->nullable()->after('product_id');
            }
        });
    }
};
