<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unsignedInteger('order_detail_id');
            $table->foreign('order_detail_id')->references('id')->on('order_details')->cascadeOnDelete();
            $table->foreignId('product_warranty_tier_id')->nullable()->constrained('product_warranty_tiers')->nullOnDelete();
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_warranty_id')->nullable()->constrained('supplier_warranties')->nullOnDelete();

            $table->string('warranty_type');
            $table->integer('warranty_days')->default(0);
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->decimal('warranty_price', 12, 2)->default(0);

            $table->string('status')->default('active');

            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
            $table->index('warranty_end_date');
            $table->unique('order_detail_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_sales');
    }
};
