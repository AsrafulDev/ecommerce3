<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedicated serial-number list per batch (owner spec D1).
     *  - stock_sn : serial numbers received / available in this batch
     *  - sold_sn  : serial numbers sold/assigned from this batch
     * Plain indexes only (no DB-level FKs on purpose — product_variant_prices
     * rows are deleted/re-created on product edit and that must not cascade).
     */
    public function up(): void
    {
        if (!Schema::hasTable('batch_sn_lists')) {
            Schema::create('batch_sn_lists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('variant_id')->nullable();
                $table->unsignedBigInteger('purchase_id')->nullable();
                $table->unsignedBigInteger('batch_id')->nullable();
                $table->json('stock_sn')->nullable()->comment('Serial numbers available in this batch');
                $table->json('sold_sn')->nullable()->comment('Serial numbers sold/assigned from this batch');
                $table->timestamps();

                $table->index('product_id');
                $table->index('variant_id');
                $table->index('purchase_id');
                $table->index('batch_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_sn_lists');
    }
};
