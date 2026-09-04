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
        Schema::dropIfExists('homepage_layout_sections');
        DB::statement('CREATE TABLE `homepage_layout_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `layout_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_visible` tinyint(4) NOT NULL DEFAULT 1,
  `columns_config` varchar(50) NOT NULL DEFAULT \'col-sm-12\',
  `extra_settings` longtext DEFAULT NULL,
  `breakpoints` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `homepage_layout_sections_layout_section_unique` (`layout_id`,`section_id`),
  KEY `homepage_layout_sections_section_id_index` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_layout_sections');
    }
};
