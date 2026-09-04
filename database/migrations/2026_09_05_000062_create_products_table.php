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
        Schema::dropIfExists('products');
        DB::statement('CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` bigint(20) unsigned DEFAULT NULL,
  `childcategory_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `product_code` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `barcode_type` varchar(20) NOT NULL DEFAULT \'C128\',
  `purchase_price` int(11) NOT NULL,
  `costing_method` enum(\'lifo\',\'fifo\',\'average\') NOT NULL DEFAULT \'average\',
  `allocation_method` varchar(10) NOT NULL DEFAULT \'FIFO\' COMMENT \'Storefront allocation: FIFO | LIFO | AVG (default FIFO)\',
  `supplier_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `old_price` int(11) DEFAULT NULL,
  `new_price` int(11) NOT NULL,
  `website_price` decimal(14,2) DEFAULT NULL COMMENT \'Cached price from the active website batch (fast catalog queries)\',
  `website_stock` int(11) NOT NULL DEFAULT 0 COMMENT \'Cached sum of website-enabled batch stock\',
  `advance_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
  `allow_negative_stock` tinyint(1) NOT NULL DEFAULT 0,
  `sold` int(11) DEFAULT 0,
  `pro_unit` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `topsale` tinyint(4) DEFAULT NULL,
  `flashsale` tinyint(4) NOT NULL DEFAULT 0,
  `feature_product` tinyint(4) DEFAULT NULL,
  `campaign_id` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `publish_status` varchar(20) NOT NULL DEFAULT \'active\',
  `product_type` varchar(20) NOT NULL DEFAULT \'simple\',
  `is_digital` tinyint(4) NOT NULL DEFAULT 0,
  `digital_file` varchar(255) DEFAULT NULL,
  `download_limit` int(11) DEFAULT NULL,
  `download_expire_days` int(11) DEFAULT NULL,
  `facebook_posted_at` timestamp NULL DEFAULT NULL,
  `approval_status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'approved\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_wholesale` tinyint(4) NOT NULL DEFAULT 0,
  `is_sn_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'Require serial number entry per unit when selling\',
  `wholesale_price` decimal(14,2) DEFAULT NULL,
  `reseller_price` decimal(10,2) DEFAULT NULL,
  `min_wholesale_quantity` int(11) NOT NULL DEFAULT 1,
  `free_delivery` tinyint(4) NOT NULL DEFAULT 0,
  `warranty_method` varchar(20) NOT NULL DEFAULT \'active\' COMMENT \'active: show warranty, inactive: hide warranty, hidden: hide from frontend but keep data\',
  `pro_video_type` varchar(20) DEFAULT NULL,
  `pro_video` varchar(255) DEFAULT NULL,
  `pro_video_path` varchar(300) DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_product_code_unique` (`product_code`),
  KEY `products_vendor_id_index` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
