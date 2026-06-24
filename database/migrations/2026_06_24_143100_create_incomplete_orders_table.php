<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomplete_orders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('phone', 50);
            $table->text('address')->nullable();
            $table->json('items')->nullable();
            $table->string('product_image', 255)->nullable();
            $table->string('product_link', 255)->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomplete_orders');
    }
};
