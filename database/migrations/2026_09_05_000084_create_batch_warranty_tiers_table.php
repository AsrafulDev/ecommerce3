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
        Schema::dropIfExists('batch_warranty_tiers');
        DB::statement('CREATE TABLE `batch_warranty_tiers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_batch_id` bigint(20) unsigned NOT NULL,
  `variant_price_id` bigint(20) unsigned DEFAULT NULL,
  `warranty_tier_id` bigint(20) unsigned NOT NULL,
  `additional_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_warranty_unique` (`stock_batch_id`,`variant_price_id`,`warranty_tier_id`),
  KEY `batch_warranty_tiers_variant_price_id_foreign` (`variant_price_id`),
  KEY `batch_warranty_tiers_warranty_tier_id_index` (`warranty_tier_id`),
  CONSTRAINT `batch_warranty_tiers_stock_batch_id_foreign` FOREIGN KEY (`stock_batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `batch_warranty_tiers_variant_price_id_foreign` FOREIGN KEY (`variant_price_id`) REFERENCES `product_variant_prices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `batch_warranty_tiers_warranty_tier_id_foreign` FOREIGN KEY (`warranty_tier_id`) REFERENCES `product_warranty_tiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_warranty_tiers');
    }
};
