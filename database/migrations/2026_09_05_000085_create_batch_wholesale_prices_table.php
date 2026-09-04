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
        Schema::dropIfExists('batch_wholesale_prices');
        DB::statement('CREATE TABLE `batch_wholesale_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_batch_id` bigint(20) unsigned NOT NULL,
  `variant_price_id` bigint(20) unsigned DEFAULT NULL,
  `min_quantity` int(10) unsigned NOT NULL,
  `max_quantity` int(10) unsigned DEFAULT NULL,
  `wholesale_price` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_wholesale_prices_variant_price_id_foreign` (`variant_price_id`),
  KEY `batch_wholesale_prices_stock_batch_id_variant_price_id_index` (`stock_batch_id`,`variant_price_id`),
  CONSTRAINT `batch_wholesale_prices_stock_batch_id_foreign` FOREIGN KEY (`stock_batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `batch_wholesale_prices_variant_price_id_foreign` FOREIGN KEY (`variant_price_id`) REFERENCES `product_variant_prices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_wholesale_prices');
    }
};
