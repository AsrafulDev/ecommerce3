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
        if (!Schema::hasTable('stock_batches')) {
            Schema::create('stock_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('variant_price_id')->nullable();
                $table->unsignedBigInteger('purchase_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('batch_no', 50)->nullable();
                $table->integer('quantity')->default(0)->comment('positive=in, negative=out');
                $table->integer('remaining_qty')->default(0)->comment('current available from this batch');
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('selling_price', 14, 2)->nullable();
                $table->date('mfg_date')->nullable();
                $table->date('exp_date')->nullable();
                $table->enum('type', ['in', 'out'])->default('in');
                $table->string('reference_type', 50)->nullable()->comment('purchase, sale_return, purchase_return, adjustment');
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->timestamps();

                $table->index('product_id');
                $table->index('purchase_id');
                $table->index('supplier_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
