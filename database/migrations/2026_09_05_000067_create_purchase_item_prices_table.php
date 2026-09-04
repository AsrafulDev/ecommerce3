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
        Schema::dropIfExists('purchase_item_prices');
        DB::statement('CREATE TABLE `purchase_item_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_item_id` bigint(20) unsigned NOT NULL,
  `variant_price_id` bigint(20) unsigned DEFAULT NULL,
  `selling_price` decimal(14,2) NOT NULL,
  `mrp` decimal(14,2) DEFAULT NULL,
  `wholesale_price` decimal(14,2) DEFAULT NULL,
  `wholesale_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`wholesale_tiers`)),
  `warranty_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`warranty_tiers`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_item_prices_purchase_item_id_foreign` (`purchase_item_id`),
  KEY `purchase_item_prices_variant_price_id_index` (`variant_price_id`),
  CONSTRAINT `purchase_item_prices_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_item_prices');
    }
};
