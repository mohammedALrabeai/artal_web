<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class UnassignedEmployeesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // جلب الموظفين غير المخصصين لأي سلوت
        return EmployeeProjectRecord::whereNull('shift_slot_id')
            ->where('status', true)
            ->whereNull('end_date')
            ->with(['employee', 'shift', 'zone'])
            ->get()
            ->map(function ($record) {
                return [
                    'employee_id'   => $record->employee?->id,
                    'employee_name' => $record->employee
                        ? "{$record->employee->first_name} {$record->employee->father_name} {$record->employee->family_name}"
                        : '-',
                    'national_id'   => $record->employee?->national_id,
                    'zone'          => $record->zone?->name,
                    'shift'         => $record->shift?->name,
                    'start_date'    => $record->start_date,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'معرف الموظف',
            'اسم الموظف',
            'رقم الهوية',
            'الموقع',
            'الوردية',
            'تاريخ البداية',
        ];
    }
}
