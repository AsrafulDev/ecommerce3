<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->decimal('discount', 14, 2);
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('min_order_amount', 14, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->integer('max_uses')->default(0);
            $table->integer('used_count')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('coupons'); }
};
