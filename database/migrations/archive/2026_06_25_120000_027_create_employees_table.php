<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('employee_id', 255);
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 255)->nullable();
            $table->string('designation', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->date('joining_date');
            $table->decimal('basic_salary', 14,2)->default(0);
            $table->text('address')->nullable();
            $table->string('nid', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account', 255)->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->integer('created_by')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('employee_id');
            $table->unique('email');
            $table->index('employee_id');
            $table->index('user_id');
            $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
