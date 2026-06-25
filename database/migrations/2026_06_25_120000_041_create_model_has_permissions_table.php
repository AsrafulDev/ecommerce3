<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->bigInteger('permission_id')->unsigned();
            $table->string('model_type', 255);
            $table->bigInteger('model_id')->unsigned();
            $table->index('model_id');
            // CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');
    }
};
