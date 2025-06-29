<?php

// app/Exports/UnassignedEmployeesExport.php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnassignedEmployeesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // استخراج جميع الإسنادات النشطة التي لا يوجد لها سلوت
        $records = EmployeeProjectRecord::where('status', true)
            ->whereNull('end_date')
            ->whereNull('shift_slot_id')
            ->with(['employee', 'shift', 'zone', 'project'])
            ->get();

        return $records->map(function ($record) {
            return [
                'اسم الموظف'      => optional($record->employee)->name ?? '',
                'رقم الهوية'      => optional($record->employee)->national_id ?? '',
                'الجوال'          => optional($record->employee)->mobile_number ?? '',
                'المشروع'         => optional($record->project)->name ?? '',
                'الموقع'          => optional($record->zone)->name ?? '',
                'الوردية'         => optional($record->shift)->name ?? '',
                'تاريخ البداية'   => $record->start_date,
                'تاريخ النهاية'   => $record->end_date,
                'رقم السجل'       => $record->id,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'اسم الموظف',
            'رقم الهوية',
            'الجوال',
            'المشروع',
            'الموقع',
            'الوردية',
            'تاريخ البداية',
            'تاريخ النهاية',
            'رقم السجل',
        ];
    }
}
