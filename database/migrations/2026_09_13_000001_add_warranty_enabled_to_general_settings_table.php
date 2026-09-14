<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('general_settings', 'warranty_enabled')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->boolean('warranty_enabled')->default(true)->after('show_category_wise_products');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('general_settings', 'warranty_enabled')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('warranty_enabled');
            });
        }
    }
};