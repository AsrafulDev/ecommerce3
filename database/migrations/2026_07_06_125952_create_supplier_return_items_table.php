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
        if (!Schema::hasTable('supplier_return_items')) {
            Schema::create('supplier_return_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_return_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('variant_price_id')->nullable();
                $table->unsignedBigInteger('batch_id')->nullable();
                $table->integer('qty');
                $table->decimal('unit_cost', 14, 2);
                $table->decimal('line_total', 14, 2);
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index('supplier_return_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_return_items');
    }
};
