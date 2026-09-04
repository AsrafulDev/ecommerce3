<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');          // orders.id is INT UNSIGNED
            $table->unsignedInteger('customer_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 55)->default('Cash');
            $table->string('trx_note', 255)->nullable(); // transaction id / reference
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
