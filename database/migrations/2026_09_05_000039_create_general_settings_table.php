<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table-wise squashed migration (generated from SHOW CREATE TABLE).
     */
    public function up(): void
    {
        Schema::dropIfExists('general_settings');
        DB::statement('CREATE TABLE `general_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(55) NOT NULL,
  `default_language` varchar(10) NOT NULL DEFAULT \'en\',
  `admin_language` varchar(10) NOT NULL DEFAULT \'en\',
  `white_logo` varchar(255) NOT NULL,
  `dark_logo` varchar(255) NOT NULL,
  `favicon` varchar(255) NOT NULL,
  `copyright` varchar(155) DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `theme_id` bigint(20) unsigned DEFAULT NULL,
  `active_layout_id` bigint(20) unsigned DEFAULT NULL,
  `duplicate_order_api_key` varchar(255) DEFAULT NULL,
  `duplicate_order_api_url` varchar(255) DEFAULT NULL,
  `duplicate_order_method` varchar(10) NOT NULL DEFAULT \'POST\',
  `duplicate_order_phone_key` varchar(50) NOT NULL DEFAULT \'phone\',
  `fraud_check_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `footer_about_text` text DEFAULT NULL,
  `google_play_link` varchar(255) DEFAULT NULL,
  `app_store_link` varchar(255) DEFAULT NULL,
  `update_api_url` varchar(255) DEFAULT NULL,
  `update_script_name` varchar(100) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `license_key` varchar(100) DEFAULT NULL,
  `show_all_products` tinyint(4) NOT NULL DEFAULT 1,
  `show_category_wise_products` tinyint(4) NOT NULL DEFAULT 1,
  `flash_sale_end_date` datetime DEFAULT NULL,
  `hot_deal_end_date` datetime DEFAULT NULL,
  `og_baner` varchar(255) DEFAULT NULL,
  `vendor_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `reseller_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `order_limit_time` int(11) NOT NULL DEFAULT 48,
  `order_limit_qty` int(11) NOT NULL DEFAULT 2,
  `top_headline` varchar(500) DEFAULT NULL,
  `facebook_page_username` varchar(255) DEFAULT NULL,
  `primary_color` varchar(7) DEFAULT \'#0d6efd\',
  `secodery_color` varchar(7) DEFAULT \'#198754\',
  `footer_color` varchar(7) DEFAULT \'#222222\',
  `copyright_color` varchar(7) DEFAULT \'#111111\',
  `order_policy` text DEFAULT NULL,
  `checkout_note` text DEFAULT NULL,
  `default_costing_method` enum(\'lifo\',\'fifo\',\'average\') NOT NULL DEFAULT \'average\',
  `header_all_category_button` tinyint(4) DEFAULT 1,
  `header_all_category_type` varchar(50) DEFAULT \'mega\',
  `product_card_style` varchar(50) NOT NULL DEFAULT \'default\',
  `pc_home_desktop` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `pc_home_laptop` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `pc_home_tablet` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `pc_home_phone` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `pc_other_desktop` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `pc_other_laptop` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `pc_other_tablet` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `pc_other_phone` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `pc_title_lines` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `pc_image_height` int(10) unsigned NOT NULL DEFAULT 200,
  `header_style` varchar(50) DEFAULT \'classic\',
  `footer_style` varchar(50) DEFAULT \'classic\',
  `header_top_bar` tinyint(4) DEFAULT 1,
  `header_sticky` tinyint(4) DEFAULT 1,
  `footer_columns` int(11) DEFAULT 4,
  `header_components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`header_components`)),
  `footer_components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`footer_components`)),
  PRIMARY KEY (`id`),
  KEY `general_settings_theme_id_index` (`theme_id`),
  KEY `general_settings_active_layout_id_index` (`active_layout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
