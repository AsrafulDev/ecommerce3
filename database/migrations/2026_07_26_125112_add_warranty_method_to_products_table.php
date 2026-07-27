<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'warranty_method')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('warranty_method', 20)->default('active')->after('free_delivery')
                    ->comment('active: show warranty, inactive: hide warranty, hidden: hide from frontend but keep data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('warranty_method');
        });
    }
};
