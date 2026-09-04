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
        Schema::dropIfExists('batch_variant_prices');
        DB::statement('CREATE TABLE `batch_variant_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_batch_id` bigint(20) unsigned NOT NULL,
  `variant_price_id` bigint(20) unsigned NOT NULL,
  `price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `old_price` decimal(14,2) DEFAULT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_variant_prices_stock_batch_id_variant_price_id_unique` (`stock_batch_id`,`variant_price_id`),
  KEY `batch_variant_prices_variant_price_id_index` (`variant_price_id`),
  CONSTRAINT `batch_variant_prices_stock_batch_id_foreign` FOREIGN KEY (`stock_batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `batch_variant_prices_variant_price_id_foreign` FOREIGN KEY (`variant_price_id`) REFERENCES `product_variant_prices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_variant_prices');
    }
};
