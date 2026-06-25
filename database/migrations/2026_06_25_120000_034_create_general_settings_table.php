<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
