<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->string('refund_id', 255);
            $table->decimal('amount', 14,2);
            $table->decimal('shipping_charge', 14,2)->default(0);
            $table->text('reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->enum('refund_method', ['original_payment', 'bkash', 'nagad', 'bank', 'manual'])->default('original_payment');
            $table->string('refund_account', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('refund_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
