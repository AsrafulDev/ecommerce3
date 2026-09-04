<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->nullable()->constrained('warranty_claims')->nullOnDelete();
            $table->foreignId('warranty_sale_id')->nullable()->constrained('warranty_sales')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('original_serial_number', 100)->nullable();   // customer's returned unit
            $table->string('replacement_serial_number', 100)->nullable();// new unit given to customer
            $table->string('damage_type')->default('partial');           // partial | full
            $table->string('status')->default('on_warranty');            // on_warranty | supplier_hold | in_service | resellable | unsellable | discarded
            $table->string('condition_note', 255)->nullable();
            $table->string('accessories', 255)->nullable();
            $table->decimal('service_cost', 12, 2)->default(0);          // cost to repair → resellable
            $table->decimal('damage_cost', 12, 2)->default(0);           // write-off → unsellable
            $table->decimal('resell_price', 12, 2)->nullable();          // set when → resellable
            $table->dateTime('received_at')->nullable();                 // when damaged unit came in
            $table->dateTime('disposed_at')->nullable();
            // users.id is INT UNSIGNED — no FK constraint (matches existing codebase pattern)
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_products');
    }
};
