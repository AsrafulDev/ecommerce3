<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 155);
            $table->string('slug', 155);
            $table->string('phone', 55);
            $table->string('email', 55)->nullable();
            $table->double('balance', 14,2)->default(0);
            $table->integer('district')->nullable();
            $table->integer('area')->nullable();
            $table->string('address', 255)->nullable();
            $table->integer('verify')->nullable();
            $table->string('image', 255)->default('public/uploads/default/user.png');
            $table->string('password', 255);
            $table->string('remember_token', 255)->nullable();
            $table->string('status', 55);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
