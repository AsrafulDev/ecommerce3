<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courierapis')) {
            Schema::create('courierapis', function (Blueprint $table) {
            $table->id();
            $table->string('type', 55)->nullable();
            $table->string('api_key', 155)->nullable();
            $table->string('secret_key', 155)->nullable();
            $table->string('client_id', 255)->nullable();
            $table->string('client_secret', 255)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('url', 99)->nullable();
            $table->string('token', 350)->nullable();
            $table->string('webhook_url', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->tinyInteger('status')->default(1);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courierapis');
    }
};
