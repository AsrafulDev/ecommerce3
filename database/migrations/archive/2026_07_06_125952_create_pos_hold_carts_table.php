<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pos_hold_carts')) {
            Schema::create('pos_hold_carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_name', 255)->nullable();
                $table->string('customer_phone', 50)->nullable();
                $table->json('cart_data');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('shipping_charge', 15, 2)->default(0);
                $table->decimal('grand_total', 15, 2)->default(0);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('held_by')->nullable();
                $table->timestamp('held_at')->nullable();
                $table->timestamp('restored_at')->nullable();
                $table->enum('status', ['held', 'restored', 'converted', 'cancelled'])->default('held');
                $table->timestamps();

                $table->index('customer_id');
                $table->index('held_by');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_hold_carts');
    }
};
