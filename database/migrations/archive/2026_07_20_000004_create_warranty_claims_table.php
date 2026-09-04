<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_sale_id')->constrained('warranty_sales')->cascadeOnDelete();
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->unsignedInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('claim_number')->unique();
            $table->text('issue_description');
            $table->string('issue_type')->nullable();

            $table->json('attachments')->nullable();

            $table->string('status')->default('submitted');
            $table->text('resolution')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->decimal('servicing_cost', 12, 2)->default(0);
            $table->boolean('store_bears_cost')->default(false);

            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
            $table->index('claim_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
