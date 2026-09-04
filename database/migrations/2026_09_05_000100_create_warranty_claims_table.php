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
        Schema::dropIfExists('warranty_claims');
        DB::statement('CREATE TABLE `warranty_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warranty_sale_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `claim_number` varchar(255) NOT NULL,
  `issue_description` text NOT NULL,
  `issue_type` varchar(255) DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `status` varchar(255) NOT NULL DEFAULT \'submitted\',
  `resolution` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `servicing_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `store_bears_cost` tinyint(1) NOT NULL DEFAULT 0,
  `claimed_at` timestamp NULL DEFAULT NULL,
  `product_received_at` timestamp NULL DEFAULT NULL,
  `receive_challan_no` varchar(50) DEFAULT NULL,
  `receive_notes` text DEFAULT NULL,
  `sent_to_supplier_at` timestamp NULL DEFAULT NULL,
  `supplier_challan_no` varchar(50) DEFAULT NULL,
  `sent_supplier_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_send_notes` text DEFAULT NULL,
  `returned_from_supplier_at` timestamp NULL DEFAULT NULL,
  `supplier_return_challan_no` varchar(50) DEFAULT NULL,
  `replacement_sn` varchar(100) DEFAULT NULL,
  `replacement_order_detail_id` int(10) unsigned DEFAULT NULL,
  `return_type` enum(\'repaired\',\'replaced\',\'refunded\') DEFAULT NULL,
  `supplier_return_notes` text DEFAULT NULL,
  `ready_for_delivery_at` timestamp NULL DEFAULT NULL,
  `delivery_challan_no` varchar(50) DEFAULT NULL,
  `delivered_to_customer_at` timestamp NULL DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `supplier_charge` decimal(15,2) DEFAULT NULL,
  `supplier_expense_id` bigint(20) unsigned DEFAULT NULL,
  `customer_charge` decimal(15,2) DEFAULT NULL,
  `customer_earning_fund_id` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_claims_claim_number_unique` (`claim_number`),
  KEY `warranty_claims_warranty_sale_id_foreign` (`warranty_sale_id`),
  KEY `warranty_claims_order_id_foreign` (`order_id`),
  KEY `warranty_claims_product_id_foreign` (`product_id`),
  KEY `warranty_claims_customer_id_index` (`customer_id`),
  KEY `warranty_claims_status_index` (`status`),
  KEY `warranty_claims_claim_number_index` (`claim_number`),
  KEY `warranty_claims_sent_supplier_id_foreign` (`sent_supplier_id`),
  KEY `warranty_claims_supplier_expense_id_foreign` (`supplier_expense_id`),
  KEY `warranty_claims_customer_earning_fund_id_foreign` (`customer_earning_fund_id`),
  CONSTRAINT `warranty_claims_customer_earning_fund_id_foreign` FOREIGN KEY (`customer_earning_fund_id`) REFERENCES `fund_transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_claims_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_claims_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_claims_sent_supplier_id_foreign` FOREIGN KEY (`sent_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_supplier_expense_id_foreign` FOREIGN KEY (`supplier_expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_warranty_sale_id_foreign` FOREIGN KEY (`warranty_sale_id`) REFERENCES `warranty_sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
