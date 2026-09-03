<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrUniqueIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_attendance_allows_multiple_rows_per_employee_on_different_days(): void
    {
        $employee = Employee::create([
            'name' => 'HR Employee',
            'phone' => '01700000001',
            'email' => 'hr1@example.com',
            'employee_id' => 'EMP-00001',
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        EmployeeAttendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);

        EmployeeAttendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);

        $this->assertSame(2, EmployeeAttendance::where('employee_id', $employee->id)->count());
    }

    public function test_employee_salary_allows_multiple_rows_per_employee_on_different_months(): void
    {
        $employee = Employee::create([
            'name' => 'HR Salary Employee',
            'phone' => '01700000002',
            'email' => 'hr2@example.com',
            'employee_id' => 'EMP-00002',
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'salary_month' => '2026-09',
            'total_days' => 30,
            'working_days' => 30,
            'basic_salary' => 10000,
            'net_salary' => 10000,
            'status' => 'pending',
        ]);

        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'salary_month' => '2026-10',
            'total_days' => 31,
            'working_days' => 31,
            'basic_salary' => 10000,
            'net_salary' => 10000,
            'status' => 'pending',
        ]);

        $this->assertSame(2, EmployeeSalary::where('employee_id', $employee->id)->count());
    }
}
