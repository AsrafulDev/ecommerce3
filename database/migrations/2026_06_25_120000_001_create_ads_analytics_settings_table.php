<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ads_analytics_settings')) {
            Schema::create('ads_analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 255);
            $table->tinyInteger('is_active')->default(0);
            $table->text('access_token')->nullable();
            $table->string('ad_account_id', 255)->nullable();
            $table->string('app_id', 255)->nullable();
            $table->string('app_secret', 255)->nullable();
            $table->string('refresh_token', 255)->nullable();
            $table->string('client_id', 255)->nullable();
            $table->string('client_secret', 255)->nullable();
            $table->longText('extra_config')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_analytics_settings');
    }
};
