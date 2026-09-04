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
        Schema::dropIfExists('fund_transaction_logs');
        DB::statement('CREATE TABLE `fund_transaction_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fund_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `action` enum(\'edit\',\'delete\') NOT NULL,
  `old_direction` enum(\'in\',\'out\') DEFAULT NULL,
  `new_direction` enum(\'in\',\'out\') DEFAULT NULL,
  `old_amount` decimal(15,2) DEFAULT NULL,
  `new_amount` decimal(15,2) DEFAULT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `old_note` varchar(255) DEFAULT NULL,
  `new_note` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transaction_logs');
    }
};
