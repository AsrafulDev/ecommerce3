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
        Schema::dropIfExists('orders');
        DB::statement('CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` varchar(55) NOT NULL,
  `amount` int(11) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` int(11) NOT NULL,
  `shipping_charge` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `order_status` varchar(55) NOT NULL,
  `note` text DEFAULT NULL,
  `order_note` text DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT \'pending\',
  `order_type` varchar(20) NOT NULL DEFAULT \'online\',
  `coupon_code` varchar(50) DEFAULT NULL,
  `courier_type` varchar(255) DEFAULT NULL,
  `courier_tracking_id` varchar(255) DEFAULT NULL,
  `courier_sent_at` timestamp NULL DEFAULT NULL,
  `fraud_success_rate` decimal(5,2) DEFAULT NULL,
  `pathao_rate` decimal(5,2) DEFAULT NULL,
  `redx_rate` decimal(5,2) DEFAULT NULL,
  `steadfast_rate` decimal(5,2) DEFAULT NULL,
  `is_duplicate_order` tinyint(4) NOT NULL DEFAULT 0,
  `duplicate_order_count` int(11) NOT NULL DEFAULT 0,
  `duplicate_order_rate` decimal(5,2) DEFAULT NULL,
  `last_duplicate_order_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_invoice_id_unique` (`invoice_id`),
  KEY `orders_customer_id_index` (`customer_id`),
  KEY `orders_order_status_index` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
