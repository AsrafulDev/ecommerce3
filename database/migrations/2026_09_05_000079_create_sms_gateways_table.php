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
        Schema::dropIfExists('sms_gateways');
        DB::statement('CREATE TABLE `sms_gateways` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(99) DEFAULT NULL,
  `method` varchar(10) NOT NULL DEFAULT \'POST\',
  `phone_key` varchar(50) NOT NULL DEFAULT \'number\',
  `message_key` varchar(50) NOT NULL DEFAULT \'message\',
  `api_key` varchar(155) DEFAULT NULL,
  `serderid` varchar(155) DEFAULT NULL,
  `status` varchar(25) DEFAULT NULL,
  `forget_pass` tinyint(4) DEFAULT 0,
  `order_confirm` tinyint(4) DEFAULT 0,
  `order_cancel` tinyint(4) DEFAULT 0,
  `order` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateways');
    }
};
