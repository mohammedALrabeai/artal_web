<?php 
namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class EmployeeHelper
{
    public static function getEmployeeLabel(int $employeeId): ?string
    {
        return cache()->remember("employee_label_{$employeeId}", 60, function () use ($employeeId) {
            $employee = DB::table('employees')
                ->select([
                    'id',
                    'first_name',
                    'father_name',
                    'grandfather_name',
                    'family_name',
                    'national_id',
                ])
                ->where('status', true)
                ->where('id', $employeeId)
                ->first();

            if (! $employee) return null;

            $fullName = implode(' ', array_filter([
                $employee->first_name,
                $employee->father_name,
                $employee->grandfather_name,
                $employee->family_name,
            ]));

            return "{$fullName} - {$employee->national_id} ({$employee->id})";
        });
    }
}
