<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the selected product-card design (product_card_style) to general_settings.
 * The admin picks one of the frontend product-card designs from Theme System → Product Design.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'product_card_style')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('product_card_style', 50)->default('default')->after('header_all_category_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', 'product_card_style')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('product_card_style');
            });
        }
    }
};
