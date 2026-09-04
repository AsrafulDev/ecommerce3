<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fund_transaction_logs')) {
            Schema::create('fund_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fund_transaction_id')->unsigned()->nullable();
            $table->enum('action', ['edit', 'delete']);
            $table->enum('old_direction', ['in', 'out'])->nullable();
            $table->enum('new_direction', ['in', 'out'])->nullable();
            $table->decimal('old_amount', 15,2)->nullable();
            $table->decimal('new_amount', 15,2)->nullable();
            $table->decimal('balance_before', 15,2);
            $table->decimal('balance_after', 15,2);
            $table->string('old_note', 255)->nullable();
            $table->string('new_note', 255)->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('performed_by')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transaction_logs');
    }
};
