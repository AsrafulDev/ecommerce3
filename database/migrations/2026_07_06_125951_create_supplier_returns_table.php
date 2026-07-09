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
        if (!Schema::hasTable('supplier_returns')) {
            Schema::create('supplier_returns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id');
                $table->unsignedBigInteger('purchase_id')->nullable();
                $table->string('return_no', 50);
                $table->date('return_date');
                $table->integer('total_qty')->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('supplier_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_returns');
    }
};
