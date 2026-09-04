<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('google_analytics_settings')) {
            Schema::create('google_analytics_settings', function (Blueprint $table) {
                $table->id();
                $table->string('measurement_id', 50)->nullable();
                $table->string('api_secret', 255)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('google_analytics_settings');
    }
};
