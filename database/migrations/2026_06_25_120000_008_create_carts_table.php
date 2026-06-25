<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->string('product_name', 255);
            $table->integer('qty');
            $table->decimal('price', 14,2);
            $table->string('size', 255)->nullable();
            $table->string('color', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
