<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_gateways')) {
            Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('url', 99)->nullable();
            $table->string('method', 10)->default('POST');
            $table->string('phone_key', 50)->default('number');
            $table->string('message_key', 50)->default('message');
            $table->string('api_key', 155)->nullable();
            $table->string('serderid', 155)->nullable();
            $table->string('status', 25)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateways');
    }
};
