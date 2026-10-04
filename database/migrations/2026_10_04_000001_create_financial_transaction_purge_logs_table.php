<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL may have created the table before rejecting an overlong
        // auto-generated index name on a previous interrupted run.
        if (Schema::hasTable('financial_transaction_purge_logs')) {
            return;
        }
        Schema::create('financial_transaction_purge_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_type', 80);
            $table->string('transaction_reference', 191)->nullable();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->dateTime('effective_date')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('party_type', 80)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->dateTime('performed_at');
            $table->text('reason');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('dependency_snapshot')->nullable();
            $table->json('before_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['transaction_type', 'source_id'], 'ftpl_type_source_idx');
            $table->index('performed_at', 'ftpl_performed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transaction_purge_logs');
    }
};
