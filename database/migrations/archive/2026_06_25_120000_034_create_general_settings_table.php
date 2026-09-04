<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('general_settings')) {
            Schema::create('general_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 55);
            $table->string('white_logo', 255);
            $table->string('dark_logo', 255);
            $table->string('favicon', 255);
            $table->string('copyright', 155)->nullable();
            $table->tinyInteger('status');
            $table->bigInteger('theme_id')->unsigned()->nullable();
            $table->bigInteger('active_layout_id')->unsigned()->nullable();
            $table->string('duplicate_order_api_key', 255)->nullable();
            $table->string('duplicate_order_api_url', 255)->nullable();
            $table->string('duplicate_order_method', 10)->default('POST');
            $table->string('duplicate_order_phone_key', 50)->default('phone');
            $table->tinyInteger('fraud_check_enabled')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('footer_about_text')->nullable();
            $table->string('google_play_link', 255)->nullable();
            $table->string('app_store_link', 255)->nullable();
            $table->string('update_api_url', 255)->nullable();
            $table->string('update_script_name', 100)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->tinyInteger('show_all_products')->default(1);
            $table->tinyInteger('show_category_wise_products')->default(1);
            $table->dateTime('flash_sale_end_date')->nullable();
            $table->dateTime('hot_deal_end_date')->nullable();
            $table->string('og_baner', 255)->nullable();
            $table->tinyInteger('vendor_enabled')->default(0);
            $table->tinyInteger('reseller_enabled')->default(0);
            $table->integer('order_limit_time')->default(48);
            $table->integer('order_limit_qty')->default(2);
            $table->index('theme_id');
            $table->index('active_layout_id');
            // CONSTRAINT `general_settings_active_layout_id_foreign` FOREIGN KEY (`active_layout_id`) REFERENCES `homepage_layouts` (`id`) ON DELETE SET NULL
            // CONSTRAINT `general_settings_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL
            });
        }

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

if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'default_language')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('default_language', 10)->default('en')->after('name');
            });
        }

$missingColumns = [
            'facebook_page_username' => "ALTER TABLE general_settings ADD facebook_page_username VARCHAR(255) NULL",
            'primary_color' => "ALTER TABLE general_settings ADD primary_color VARCHAR(7) DEFAULT '#0d6efd'",
            'secodery_color' => "ALTER TABLE general_settings ADD secodery_color VARCHAR(7) DEFAULT '#198754'",
            'footer_color' => "ALTER TABLE general_settings ADD footer_color VARCHAR(7) DEFAULT '#222222'",
            'copyright_color' => "ALTER TABLE general_settings ADD copyright_color VARCHAR(7) DEFAULT '#111111'",
            'order_policy' => "ALTER TABLE general_settings ADD order_policy TEXT NULL",
            'checkout_note' => "ALTER TABLE general_settings ADD checkout_note TEXT NULL",
        ];

        foreach ($missingColumns as $col => $sql) {
            if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', $col)) {
                DB::statement($sql);
            }
        }

if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'admin_language')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('admin_language', 10)->default('en')->after('default_language');
            });
        }

if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('general_settings', 'default_costing_method')) {
                    $table->enum('default_costing_method', ['lifo', 'fifo', 'average'])->default('average');
                }
            });
        }

// All Category Button (on/off + presentation type) for Header & Footer Builder → Header Style
        $cols = [
            'header_all_category_button' => "ALTER TABLE general_settings ADD header_all_category_button TINYINT DEFAULT 1",
            'header_all_category_type'   => "ALTER TABLE general_settings ADD header_all_category_type VARCHAR(50) DEFAULT 'mega'",
        ];

        foreach ($cols as $col => $sql) {
            if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', $col)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
