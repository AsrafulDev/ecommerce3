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
        Schema::dropIfExists('warranty_challans');
        DB::statement('CREATE TABLE `warranty_challans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warranty_claim_id` bigint(20) unsigned NOT NULL,
  `challan_type` enum(\'receive\',\'send_to_supplier\',\'receive_return\',\'delivery\') NOT NULL,
  `challan_no` varchar(50) NOT NULL,
  `challan_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`challan_data`)),
  `generated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_challans_challan_no_unique` (`challan_no`),
  KEY `warranty_challans_warranty_claim_id_foreign` (`warranty_claim_id`),
  KEY `warranty_challans_generated_by_foreign` (`generated_by`),
  CONSTRAINT `warranty_challans_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_challans_warranty_claim_id_foreign` FOREIGN KEY (`warranty_claim_id`) REFERENCES `warranty_claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_challans');
    }
};
