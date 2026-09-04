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
        Schema::dropIfExists('warranty_sales');
        DB::statement('CREATE TABLE `warranty_sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `order_detail_id` int(10) unsigned NOT NULL,
  `product_warranty_tier_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `sold_by` int(10) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `serial_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`serial_numbers`)),
  `supplier_warranty_id` bigint(20) unsigned DEFAULT NULL,
  `stock_batch_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `warranty_type` varchar(255) NOT NULL,
  `warranty_days` int(11) NOT NULL DEFAULT 0,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `warranty_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `terms` text DEFAULT NULL,
  `is_transferable` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT \'active\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_sales_order_detail_id_unique` (`order_detail_id`),
  KEY `warranty_sales_order_id_foreign` (`order_id`),
  KEY `warranty_sales_product_warranty_tier_id_foreign` (`product_warranty_tier_id`),
  KEY `warranty_sales_product_id_foreign` (`product_id`),
  KEY `warranty_sales_supplier_warranty_id_foreign` (`supplier_warranty_id`),
  KEY `warranty_sales_customer_id_index` (`customer_id`),
  KEY `warranty_sales_status_index` (`status`),
  KEY `warranty_sales_warranty_end_date_index` (`warranty_end_date`),
  KEY `warranty_sales_sold_by_foreign` (`sold_by`),
  KEY `warranty_sales_stock_batch_id_foreign` (`stock_batch_id`),
  KEY `warranty_sales_purchase_id_foreign` (`purchase_id`),
  KEY `warranty_sales_variant_id_index` (`variant_id`),
  CONSTRAINT `warranty_sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_sales_order_detail_id_foreign` FOREIGN KEY (`order_detail_id`) REFERENCES `order_details` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_sales_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_sales_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_sales_product_warranty_tier_id_foreign` FOREIGN KEY (`product_warranty_tier_id`) REFERENCES `product_warranty_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_sales_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_sales_sold_by_foreign` FOREIGN KEY (`sold_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_sales_stock_batch_id_foreign` FOREIGN KEY (`stock_batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_sales_supplier_warranty_id_foreign` FOREIGN KEY (`supplier_warranty_id`) REFERENCES `supplier_warranties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_sales');
    }
};
