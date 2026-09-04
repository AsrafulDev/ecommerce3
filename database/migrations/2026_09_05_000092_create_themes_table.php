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
        Schema::dropIfExists('themes');
        DB::statement('CREATE TABLE `themes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(4) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `preview_image` varchar(255) DEFAULT NULL,
  `primary_color` varchar(7) NOT NULL DEFAULT \'#0d6efd\',
  `secondary_color` varchar(7) NOT NULL DEFAULT \'#198754\',
  `accent_color` varchar(7) NOT NULL DEFAULT \'#ff6a00\',
  `text_color` varchar(7) NOT NULL DEFAULT \'#212529\',
  `heading_color` varchar(7) NOT NULL DEFAULT \'#111111\',
  `body_bg_color` varchar(7) NOT NULL DEFAULT \'#ffffff\',
  `header_bg_color` varchar(7) NOT NULL DEFAULT \'#ffffff\',
  `header_text_color` varchar(7) NOT NULL DEFAULT \'#212529\',
  `footer_bg_color` varchar(7) NOT NULL DEFAULT \'#1a1a1a\',
  `footer_text_color` varchar(7) NOT NULL DEFAULT \'#cccccc\',
  `copyright_bg_color` varchar(7) NOT NULL DEFAULT \'#000000\',
  `copyright_text_color` varchar(7) NOT NULL DEFAULT \'#ffffff\',
  `button_bg_color` varchar(7) NOT NULL DEFAULT \'#0d6efd\',
  `button_text_color` varchar(7) NOT NULL DEFAULT \'#ffffff\',
  `button_hover_bg_color` varchar(7) NOT NULL DEFAULT \'#0b5ed7\',
  `border_color` varchar(7) NOT NULL DEFAULT \'#dee2e6\',
  `sale_badge_bg` varchar(7) NOT NULL DEFAULT \'#dc3545\',
  `sale_badge_text` varchar(7) NOT NULL DEFAULT \'#ffffff\',
  `font_family` varchar(100) NOT NULL,
  `heading_font` varchar(100) NOT NULL,
  `body_font_size` varchar(10) NOT NULL DEFAULT \'14px\',
  `heading_font_weight` varchar(10) NOT NULL DEFAULT \'700\',
  `layout_style` enum(\'full-width\',\'boxed\',\'contained\') NOT NULL DEFAULT \'contained\',
  `border_radius` varchar(10) NOT NULL DEFAULT \'8px\',
  `card_shadow` varchar(100) NOT NULL DEFAULT \'0 2px 8px rgba(0,0,0,0.08)\',
  `custom_css` text DEFAULT NULL,
  `page_custom_css` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sidebar_bg_color` varchar(20) DEFAULT NULL,
  `sidebar_text_color` varchar(20) DEFAULT NULL,
  `topbar_bg_color` varchar(20) DEFAULT NULL,
  `admin_card_bg` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `themes_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
