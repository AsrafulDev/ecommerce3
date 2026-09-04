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
        Schema::dropIfExists('purchase_logs');
        DB::statement('CREATE TABLE `purchase_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `action` enum(\'create\',\'edit\',\'delete\') NOT NULL,
  `old_invoice_no` varchar(50) DEFAULT NULL,
  `new_invoice_no` varchar(50) DEFAULT NULL,
  `old_purchase_date` date DEFAULT NULL,
  `new_purchase_date` date DEFAULT NULL,
  `old_paid_amount` decimal(15,2) DEFAULT NULL,
  `new_paid_amount` decimal(15,2) DEFAULT NULL,
  `old_grand_total` decimal(15,2) DEFAULT NULL,
  `new_grand_total` decimal(15,2) DEFAULT NULL,
  `old_note` varchar(255) DEFAULT NULL,
  `new_note` varchar(255) DEFAULT NULL,
  `fund_balance_before` decimal(15,2) DEFAULT NULL,
  `fund_balance_after` decimal(15,2) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `performed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_logs');
    }
};
