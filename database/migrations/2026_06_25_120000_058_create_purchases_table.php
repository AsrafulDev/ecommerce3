<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15,2);
            $table->text('note')->nullable();
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->decimal('grand_total', 15,2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->bigInteger('supplier_id')->unsigned()->nullable();
            $table->decimal('due_amount', 15,2)->default(0);
            });
        }

if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'invoice_no')) {
                    $table->string('invoice_no', 50)->nullable()->after('supplier_id');
                }
                if (!Schema::hasColumn('purchases', 'total_qty')) {
                    $table->integer('total_qty')->default(0)->after('purchase_date');
                }
                if (!Schema::hasColumn('purchases', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0)->after('total_qty');
                }
                if (!Schema::hasColumn('purchases', 'discount')) {
                    $table->decimal('discount', 15, 2)->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn('purchases', 'shipping_cost')) {
                    $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount');
                }
                if (!Schema::hasColumn('purchases', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0)->after('grand_total');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
