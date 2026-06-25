<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_salary_payments')) {
            Schema::create('employee_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->unsigned();
            $table->bigInteger('salary_id')->unsigned()->nullable();
            $table->string('payment_id', 255);
            $table->string('payment_month', 255);
            $table->decimal('amount', 14,2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'bkash', 'nagad', 'rocket', 'check'])->default('bank_transfer');
            $table->string('transaction_id', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->integer('paid_by')->unsigned()->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('payment_id');
            $table->index('salary_id');
            $table->index('employee_id');
            $table->index('payment_month');
            $table->index('status');
            $table->index('payment_date');
            // CONSTRAINT `employee_salary_payments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            // CONSTRAINT `employee_salary_payments_salary_id_foreign` FOREIGN KEY (`salary_id`) REFERENCES `employee_salaries` (`id`) ON DELETE SET NULL
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_payments');
    }
};
