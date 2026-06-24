<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_transaction_id')->nullable();
            $table->enum('action', ['edit', 'delete']);
            $table->enum('old_direction', ['in', 'out'])->nullable();
            $table->enum('new_direction', ['in', 'out'])->nullable();
            $table->decimal('old_amount', 15, 2)->nullable();
            $table->decimal('new_amount', 15, 2)->nullable();
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('old_note')->nullable();
            $table->string('new_note')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transaction_logs');
    }
};
