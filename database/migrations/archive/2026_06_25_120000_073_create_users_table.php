<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('shop_name', 255)->nullable();
            $table->string('email', 255);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->tinyInteger('status');
            $table->string('image', 255)->default('public/assets/images/user.webp');
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->decimal('wallet_balance', 14,2)->default(0);
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('voter_id_front', 255)->nullable();
            $table->string('voter_id_back', 255)->nullable();
            $table->string('self_image', 255)->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unique('email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
