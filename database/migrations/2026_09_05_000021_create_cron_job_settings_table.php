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
        Schema::dropIfExists('cron_job_settings');
        DB::statement('CREATE TABLE `cron_job_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_key` varchar(80) NOT NULL,
  `job_title` varchar(150) NOT NULL,
  `job_description` text DEFAULT NULL,
  `is_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `frequency_minutes` smallint(5) unsigned NOT NULL DEFAULT 10,
  `order_limit` smallint(5) unsigned NOT NULL DEFAULT 50,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `last_run_status` varchar(20) DEFAULT NULL,
  `last_run_result` text DEFAULT NULL,
  `last_updated_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_failed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cron_job_settings_job_key_unique` (`job_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_settings');
    }
};
