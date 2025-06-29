<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnassignedEmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return EmployeeProjectRecord::where('status', true)
            ->whereNull('end_date')
            ->whereNull('shift_slot_id')
            ->with(['employee', 'project', 'zone', 'shift'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID السجل',
            'اسم الموظف',
            'رقم الهوية',
            'المشروع',
            'الموقع',
            'الوردية',
            'تاريخ البداية',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->employee?->first_name . ' ' . $record->employee?->father_name . ' ' . $record->employee?->family_name,
            $record->employee?->national_id,
            $record->project?->name,
            $record->zone?->name,
            $record->shift?->name,
            $record->start_date,
        ];
    }
}
