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
        Schema::dropIfExists('order_details');
        DB::statement('CREATE TABLE `order_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `purchase_price` int(11) NOT NULL,
  `sale_price` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `batch_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`batch_ids`)),
  `cogs` decimal(15,2) DEFAULT NULL,
  `product_color` bigint(20) unsigned DEFAULT NULL,
  `warranty_tier_id` bigint(20) unsigned DEFAULT NULL,
  `warranty_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `product_size` bigint(20) unsigned DEFAULT NULL,
  `variant_price_id` bigint(20) unsigned DEFAULT NULL,
  `product_discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_details_warranty_tier_id_foreign` (`warranty_tier_id`),
  KEY `order_details_order_id_index` (`order_id`),
  KEY `order_details_product_id_index` (`product_id`),
  CONSTRAINT `order_details_warranty_tier_id_foreign` FOREIGN KEY (`warranty_tier_id`) REFERENCES `product_warranty_tiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
