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
        Schema::dropIfExists('batch_sn_lists');
        DB::statement('CREATE TABLE `batch_sn_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `stock_sn` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'Serial numbers available in this batch\' CHECK (json_valid(`stock_sn`)),
  `sold_sn` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'Serial numbers sold/assigned from this batch\' CHECK (json_valid(`sold_sn`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_sn_lists_product_id_index` (`product_id`),
  KEY `batch_sn_lists_variant_id_index` (`variant_id`),
  KEY `batch_sn_lists_purchase_id_index` (`purchase_id`),
  KEY `batch_sn_lists_batch_id_index` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_sn_lists');
    }
};
