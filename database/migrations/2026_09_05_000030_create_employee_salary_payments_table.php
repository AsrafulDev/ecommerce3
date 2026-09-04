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
        Schema::dropIfExists('employee_salary_payments');
        DB::statement('CREATE TABLE `employee_salary_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `salary_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` varchar(255) NOT NULL,
  `payment_month` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum(\'cash\',\'bank_transfer\',\'bkash\',\'nagad\',\'rocket\',\'check\') NOT NULL DEFAULT \'bank_transfer\',
  `transaction_id` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum(\'pending\',\'paid\',\'failed\') NOT NULL DEFAULT \'pending\',
  `paid_by` int(10) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_salary_payments_payment_id_unique` (`payment_id`),
  KEY `employee_salary_payments_salary_id_index` (`salary_id`),
  KEY `employee_salary_payments_employee_id_index` (`employee_id`),
  KEY `employee_salary_payments_payment_month_index` (`payment_month`),
  KEY `employee_salary_payments_status_index` (`status`),
  KEY `employee_salary_payments_payment_date_index` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_payments');
    }
};
