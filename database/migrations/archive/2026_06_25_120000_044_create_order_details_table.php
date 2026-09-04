<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_details')) {
            Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('product_id');
            $table->string('product_name', 255);
            $table->integer('purchase_price');
            $table->integer('sale_price');
            $table->integer('qty');
            $table->bigInteger('product_color')->unsigned()->nullable();
            $table->bigInteger('product_size')->unsigned()->nullable();
            $table->bigInteger('variant_price_id')->unsigned()->nullable();
            $table->decimal('product_discount', 14,2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
