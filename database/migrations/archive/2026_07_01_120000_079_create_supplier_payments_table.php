<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('supplier_id')->unsigned();
                $table->bigInteger('purchase_id')->unsigned()->nullable();
                $table->decimal('amount', 15, 2);
                $table->date('payment_date')->nullable();
                $table->string('method', 50)->nullable();
                $table->text('note')->nullable();
                $table->bigInteger('fund_transaction_id')->unsigned()->nullable();
                $table->bigInteger('created_by')->unsigned()->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('supplier_id');
                $table->index('purchase_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
