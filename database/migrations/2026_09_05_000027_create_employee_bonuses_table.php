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
        Schema::dropIfExists('employee_bonuses');
        DB::statement('CREATE TABLE `employee_bonuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `bonus_type` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `salary_month` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum(\'pending\',\'approved\',\'paid\') NOT NULL DEFAULT \'pending\',
  `notes` text DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_bonuses_employee_id_index` (`employee_id`),
  KEY `employee_bonuses_status_index` (`status`),
  KEY `employee_bonuses_salary_month_index` (`salary_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bonuses');
    }
};
