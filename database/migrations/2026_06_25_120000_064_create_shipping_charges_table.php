<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipping_charges')) {
            Schema::create('shipping_charges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->integer('amount');
            $table->string('status', 255);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_charges');
    }
};
