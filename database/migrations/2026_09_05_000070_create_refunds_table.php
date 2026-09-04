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
        Schema::dropIfExists('refunds');
        DB::statement('CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `refund_id` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `shipping_charge` decimal(14,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(14,2) DEFAULT NULL,
  `include_shipping` tinyint(1) NOT NULL DEFAULT 1,
  `reason` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `status` enum(\'pending\',\'approved\',\'rejected\',\'processed\') NOT NULL DEFAULT \'pending\',
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `refund_method` enum(\'original_payment\',\'bkash\',\'nagad\',\'bank\',\'manual\') NOT NULL DEFAULT \'original_payment\',
  `refund_account` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `refund_account_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_refund_id_unique` (`refund_id`),
  KEY `refunds_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
