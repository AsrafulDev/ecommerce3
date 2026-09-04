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
        Schema::dropIfExists('damage_products');
        DB::statement('CREATE TABLE `damage_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warranty_claim_id` bigint(20) unsigned DEFAULT NULL,
  `warranty_sale_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `original_serial_number` varchar(100) DEFAULT NULL,
  `replacement_serial_number` varchar(100) DEFAULT NULL,
  `damage_type` varchar(255) NOT NULL DEFAULT \'partial\',
  `status` varchar(255) NOT NULL DEFAULT \'on_warranty\',
  `condition_note` varchar(255) DEFAULT NULL,
  `accessories` varchar(255) DEFAULT NULL,
  `service_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `damage_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `resell_price` decimal(12,2) DEFAULT NULL,
  `expense_id` bigint(20) unsigned DEFAULT NULL,
  `earning_fund_id` bigint(20) unsigned DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `disposed_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `damage_products_warranty_claim_id_foreign` (`warranty_claim_id`),
  KEY `damage_products_warranty_sale_id_foreign` (`warranty_sale_id`),
  KEY `damage_products_product_id_foreign` (`product_id`),
  KEY `damage_products_expense_id_foreign` (`expense_id`),
  KEY `damage_products_earning_fund_id_foreign` (`earning_fund_id`),
  KEY `damage_products_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `damage_products_earning_fund_id_foreign` FOREIGN KEY (`earning_fund_id`) REFERENCES `fund_transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damage_products_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damage_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `damage_products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damage_products_warranty_claim_id_foreign` FOREIGN KEY (`warranty_claim_id`) REFERENCES `warranty_claims` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damage_products_warranty_sale_id_foreign` FOREIGN KEY (`warranty_sale_id`) REFERENCES `warranty_sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_products');
    }
};
