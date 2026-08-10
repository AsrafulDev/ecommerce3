<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('product_wholesale_prices')) {
            Schema::create('product_wholesale_prices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned();
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->decimal('wholesale_price', 14,2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('stock')->default(0);
            });
        }

Schema::table('product_wholesale_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('product_wholesale_prices', 'variant_id')) {
                $table->bigInteger('variant_id')->unsigned()->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wholesale_prices');
    }
};
