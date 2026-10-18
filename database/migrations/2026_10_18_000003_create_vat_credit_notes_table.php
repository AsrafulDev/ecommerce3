<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A credit note reverses VAT when goods come back. It is dated when the return
 * happens, so the period that gets the reversal is the period of the return.
 * One credit note per returned or cancelled sale; purchase returns get one per event.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('vat_credit_notes')) {
            return;
        }

        Schema::create('vat_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_no', 40)->unique();
            $table->string('type', 10); // sale | purchase
            $table->unsignedBigInteger('order_id')->nullable()->unique();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedInteger('qty')->nullable();
            $table->decimal('taxable_value', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->string('reason', 255)->nullable();
            $table->dateTime('issued_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_credit_notes');
    }
};
