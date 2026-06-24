<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'show_all_products')) {
                $table->boolean('show_all_products')->default(true);
            }
            if (!Schema::hasColumn('general_settings', 'show_category_wise_products')) {
                $table->boolean('show_category_wise_products')->default(true);
            }
            if (!Schema::hasColumn('general_settings', 'flash_sale_end_date')) {
                $table->dateTime('flash_sale_end_date')->nullable();
            }
            if (!Schema::hasColumn('general_settings', 'hot_deal_end_date')) {
                $table->dateTime('hot_deal_end_date')->nullable();
            }
            if (!Schema::hasColumn('general_settings', 'og_baner')) {
                $table->string('og_baner', 255)->nullable();
            }
            if (!Schema::hasColumn('general_settings', 'vendor_enabled')) {
                $table->boolean('vendor_enabled')->default(false);
            }
            if (!Schema::hasColumn('general_settings', 'reseller_enabled')) {
                $table->boolean('reseller_enabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $columns = ['show_all_products','show_category_wise_products','flash_sale_end_date','hot_deal_end_date','og_baner','vendor_enabled','reseller_enabled'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('general_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
