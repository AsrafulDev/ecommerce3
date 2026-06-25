<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_salaries')) {
            Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->unsigned();
            $table->string('salary_month', 255);
            $table->integer('total_days');
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('working_days')->default(0);
            $table->decimal('basic_salary', 14,2)->default(0);
            $table->decimal('allowance', 14,2)->default(0);
            $table->decimal('deduction', 14,2)->default(0);
            $table->decimal('bonus', 14,2)->default(0);
            $table->decimal('overtime', 14,2)->default(0);
            $table->decimal('gross_salary', 14,2)->default(0);
            $table->decimal('net_salary', 14,2)->default(0);
            $table->enum('status', ['pending', 'calculated', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->integer('calculated_by')->unsigned()->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('employee_id');
            $table->index('salary_month');
            $table->index('status');
            // CONSTRAINT `employee_salaries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
