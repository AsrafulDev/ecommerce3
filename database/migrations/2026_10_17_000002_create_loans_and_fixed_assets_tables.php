<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loan and fixed asset registers. Loans taken or given and fixed asset purchases
 * and disposals move cash through the funds ledger; the repayment, depreciation
 * and revaluation rows here are the registers' own history.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('kind', 20);                 // general | employee
            $table->string('direction', 10);            // taken | given
            $table->string('party_name', 150);
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('loan_date');
            $table->string('note', 255)->nullable();
            $table->foreignId('fund_transaction_id')->nullable()->constrained('fund_transactions')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on');
            $table->string('note', 255)->nullable();
            $table->foreignId('fund_transaction_id')->nullable()->constrained('fund_transactions')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->date('purchase_date');
            $table->decimal('cost', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('revalued_amount', 15, 2)->nullable();
            $table->string('status', 20)->default('active'); // active | disposed
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->string('disposal_reason', 255)->nullable();
            $table->foreignId('purchase_fund_transaction_id')->nullable()->constrained('fund_transactions')->nullOnDelete();
            $table->foreignId('disposal_fund_transaction_id')->nullable()->constrained('fund_transactions')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fixed_asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->restrictOnDelete();
            $table->string('type', 20);                  // depreciation | revaluation
            $table->decimal('amount', 15, 2);
            $table->date('event_date');
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_events');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
    }
};
