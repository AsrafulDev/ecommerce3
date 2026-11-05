<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable audit for the sales reset. It has no foreign keys, so a row survives the deletion of the sale it
 * describes, and it is not part of the activity log that logs:archive-activity prunes. A row is written in
 * the same transaction as the change it records: if the audit cannot be written, the change is rolled back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaction_reset_audits')) {
            return;
        }

        Schema::create('transaction_reset_audits', function (Blueprint $table) {
            $table->id();
            $table->string('action', 30);
            $table->string('mode', 30);
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('invoice_id', 80)->nullable();
            $table->string('previous_status', 40)->nullable();
            $table->string('new_status', 40)->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('performed_by_name', 191)->nullable();
            $table->string('ip', 45)->nullable();
            $table->longText('snapshot')->nullable();
            $table->string('accounting_outcome', 40)->nullable();
            $table->longText('reversal_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_reset_audits');
    }
};
