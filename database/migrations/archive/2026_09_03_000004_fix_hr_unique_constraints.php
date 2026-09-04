<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_attendances')) {
            $this->dropUniqueIfExists('employee_attendances', 'employee_attendances_employee_id_unique');
            $this->dropUniqueIfExists('employee_attendances', 'employee_id');

            Schema::table('employee_attendances', function (Blueprint $table) {
                $table->unique(['employee_id', 'attendance_date']);
            });
        }

        if (Schema::hasTable('employee_salaries')) {
            $this->dropUniqueIfExists('employee_salaries', 'employee_salaries_employee_id_unique');
            $this->dropUniqueIfExists('employee_salaries', 'employee_id');

            Schema::table('employee_salaries', function (Blueprint $table) {
                $table->unique(['employee_id', 'salary_month']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_attendances')) {
            Schema::table('employee_attendances', function (Blueprint $table) {
                $table->dropUnique(['employee_id', 'attendance_date']);
            });
        }

        if (Schema::hasTable('employee_salaries')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                $table->dropUnique(['employee_id', 'salary_month']);
            });
        }
    }

    protected function dropUniqueIfExists(string $table, string $index): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropUnique($index);
            });
        } catch (\Throwable $e) {
            // Ignore: index may not exist or may be named differently.
        }
    }
};
