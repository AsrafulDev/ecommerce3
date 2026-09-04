<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('purchase_id')->unsigned();
                $table->bigInteger('product_id')->unsigned();
                $table->bigInteger('variant_price_id')->unsigned()->nullable();
                $table->decimal('qty', 14, 2);
                $table->decimal('unit_cost', 14, 2);
                $table->decimal('line_total', 14, 2);
                $table->decimal('returned_qty', 14, 2)->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('purchase_id');
                $table->index('product_id');
            });
        }

Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'custom_field')) {
                $table->string('custom_field', 255)->nullable()->after('returned_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
