<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claim_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained('warranty_claims')->cascadeOnDelete();
            $table->string('step');                  // supplier_delivery | customer_delivery | follow_up | repair_due ...
            $table->string('label');                 // "Supplier Return Due", "Customer Delivery Due"
            $table->dateTime('remind_at');           // the due date/time
            $table->string('status')->default('pending');  // pending | done
            $table->text('note')->nullable();
            // users.id is INT UNSIGNED — no FK constraint (matches existing codebase pattern)
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claim_reminders');
    }
};
