<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_logs')) {
            Schema::create('purchase_logs', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('purchase_id')->unsigned()->nullable();
                $table->enum('action', ['edit', 'delete']);
                $table->string('old_invoice_no', 50)->nullable();
                $table->string('new_invoice_no', 50)->nullable();
                $table->date('old_purchase_date')->nullable();
                $table->date('new_purchase_date')->nullable();
                $table->decimal('old_paid_amount', 15, 2)->nullable();
                $table->decimal('new_paid_amount', 15, 2)->nullable();
                $table->decimal('old_grand_total', 15, 2)->nullable();
                $table->decimal('new_grand_total', 15, 2)->nullable();
                $table->string('old_note', 255)->nullable();
                $table->string('new_note', 255)->nullable();
                $table->decimal('fund_balance_before', 15, 2)->nullable();
                $table->decimal('fund_balance_after', 15, 2)->nullable();
                $table->string('description', 500)->nullable();
                $table->unsignedInteger('performed_by')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_logs');
    }
};
