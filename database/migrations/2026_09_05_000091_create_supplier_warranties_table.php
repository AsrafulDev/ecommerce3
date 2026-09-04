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
        Schema::dropIfExists('supplier_warranties');
        DB::statement('CREATE TABLE `supplier_warranties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_item_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `warranty_days` int(11) NOT NULL DEFAULT 0 COMMENT \'Supplier warranty in days\',
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `warranty_type` varchar(255) NOT NULL DEFAULT \'supplier_warranty\',
  `warranty_terms` text DEFAULT NULL,
  `is_transferable` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_warranties_purchase_item_id_foreign` (`purchase_item_id`),
  KEY `supplier_warranties_product_id_warranty_end_date_index` (`product_id`,`warranty_end_date`),
  KEY `supplier_warranties_supplier_id_index` (`supplier_id`),
  KEY `supplier_warranties_batch_id_foreign` (`batch_id`),
  KEY `supplier_warranties_variant_id_index` (`variant_id`),
  CONSTRAINT `supplier_warranties_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_warranties_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_warranties_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_warranties_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_warranties');
    }
};
