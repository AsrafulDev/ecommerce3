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
        Schema::dropIfExists('warranty_claim_reminders');
        DB::statement('CREATE TABLE `warranty_claim_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warranty_claim_id` bigint(20) unsigned NOT NULL,
  `step` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `remind_at` datetime NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT \'pending\',
  `note` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warranty_claim_reminders_warranty_claim_id_foreign` (`warranty_claim_id`),
  KEY `warranty_claim_reminders_status_remind_at_index` (`status`,`remind_at`),
  CONSTRAINT `warranty_claim_reminders_warranty_claim_id_foreign` FOREIGN KEY (`warranty_claim_id`) REFERENCES `warranty_claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claim_reminders');
    }
};
