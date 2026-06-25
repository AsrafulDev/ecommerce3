<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('facebook_capi_settings')) {
            Schema::create('facebook_capi_settings', function (Blueprint $table) {
            $table->id();
            $table->string('pixel_id', 255)->nullable();
            $table->text('access_token')->nullable();
            $table->string('test_event_code', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_capi_settings');
    }
};
