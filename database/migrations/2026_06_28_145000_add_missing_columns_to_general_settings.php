<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add theme_id if missing (VPS may have older general_settings table)
        if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'theme_id')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->bigInteger('theme_id')->unsigned()->nullable()->after('status');
                $table->index('theme_id');
            });
        }

        // Add active_layout_id if missing
        if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'active_layout_id')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->bigInteger('active_layout_id')->unsigned()->nullable()->after('theme_id');
                $table->index('active_layout_id');
            });
        }

        // Add other potentially missing columns
        $missingColumns = [
            'flash_sale_end_date' => "ALTER TABLE general_settings ADD flash_sale_end_date DATETIME NULL",
            'hot_deal_end_date' => "ALTER TABLE general_settings ADD hot_deal_end_date DATETIME NULL",
            'top_headline' => "ALTER TABLE general_settings ADD top_headline VARCHAR(500) NULL",
            'og_baner' => "ALTER TABLE general_settings ADD og_baner VARCHAR(255) NULL",
            'vendor_enabled' => "ALTER TABLE general_settings ADD vendor_enabled TINYINT DEFAULT 0",
            'reseller_enabled' => "ALTER TABLE general_settings ADD reseller_enabled TINYINT DEFAULT 0",
            'order_limit_time' => "ALTER TABLE general_settings ADD order_limit_time INT DEFAULT 48",
            'order_limit_qty' => "ALTER TABLE general_settings ADD order_limit_qty INT DEFAULT 2",
            'show_all_products' => "ALTER TABLE general_settings ADD show_all_products TINYINT DEFAULT 1",
            'show_category_wise_products' => "ALTER TABLE general_settings ADD show_category_wise_products TINYINT DEFAULT 1",
            'google_play_link' => "ALTER TABLE general_settings ADD google_play_link VARCHAR(255) NULL",
            'app_store_link' => "ALTER TABLE general_settings ADD app_store_link VARCHAR(255) NULL",
        ];

        foreach ($missingColumns as $col => $sql) {
            if (!Schema::hasColumn('general_settings', $col)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
