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
        Schema::dropIfExists('facebook_page_settings');
        DB::statement('CREATE TABLE `facebook_page_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` varchar(255) DEFAULT NULL,
  `page_access_token` text DEFAULT NULL,
  `page_name` varchar(255) DEFAULT NULL,
  `auto_post_new_products` tinyint(4) NOT NULL DEFAULT 0,
  `post_template` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_page_settings');
    }
};
