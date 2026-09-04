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
        Schema::dropIfExists('stock_batches');
        DB::statement('CREATE TABLE `stock_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_price_id` bigint(20) unsigned DEFAULT NULL,
  `is_all_variants` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'TRUE = batch applies to every variant combination (shared pool)\',
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0 COMMENT \'positive=in, negative=out\',
  `remaining_qty` int(11) NOT NULL DEFAULT 0 COMMENT \'current available from this batch\',
  `sn_stock` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'Serial numbers available in this batch\' CHECK (json_valid(`sn_stock`)),
  `sn_sold` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'Serial numbers sold/assigned from this batch\' CHECK (json_valid(`sn_sold`)),
  `unit_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT \'Total cost for this batch (quantity x unit_cost)\',
  `selling_price` decimal(14,2) DEFAULT NULL,
  `mrp` decimal(14,2) DEFAULT NULL COMMENT \'Compare-at / strike-through price for this batch\',
  `wholesale_price` decimal(14,2) DEFAULT NULL COMMENT \'Quick single wholesale price for this batch\',
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `is_active_for_website` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'One per product — the batch the website shows & prices from\',
  `pos_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT \'Whether this batch is selectable in POS\',
  `has_purchase_warranty` tinyint(1) NOT NULL DEFAULT 0,
  `has_sell_warranty` tinyint(1) NOT NULL DEFAULT 0,
  `has_wholesale` tinyint(1) NOT NULL DEFAULT 0,
  `auto_advance` tinyint(1) NOT NULL DEFAULT 1 COMMENT \'Auto-activate next FIFO batch when this one sells out\',
  `is_manual_price` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'Price set manually vs inherited from product\',
  `price_updated_at` timestamp NULL DEFAULT NULL,
  `price_updated_by` bigint(20) unsigned DEFAULT NULL,
  `custom_field` varchar(255) DEFAULT NULL,
  `type` enum(\'in\',\'out\') NOT NULL DEFAULT \'in\',
  `reference_type` varchar(50) DEFAULT NULL COMMENT \'purchase, sale_return, purchase_return, adjustment\',
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_batches_product_id_index` (`product_id`),
  KEY `stock_batches_purchase_id_index` (`purchase_id`),
  KEY `stock_batches_supplier_id_index` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
