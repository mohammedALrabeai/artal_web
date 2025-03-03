<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeProjectRecordsExport implements FromQuery, ShouldAutoSize, WithCustomCsvSettings, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return EmployeeProjectRecord::query()
            ->with(['employee', 'project', 'zone', 'shift']); // ✅ جلب العلاقات المطلوبة لتسريع الاستعلام
    }

    public function headings(): array
    {
        return [
            'الاسم الكامل', 'رقم الهوية', 'المشروع', 'الموقع', 'الوردية',
            'تاريخ البدء', 'تاريخ الانتهاء', 'الحالة',
        ];
    }

    public function map($record): array
    {
        // ✅ تجميع الاسم الكامل في عمود واحد
        $fullName = trim(implode(' ', array_filter([
            $record->employee->first_name ?? '',
            $record->employee->father_name ?? '',
            $record->employee->grandfather_name ?? '',
            $record->employee->family_name ?? '',
        ])));

        return [
            $fullName, // ✅ وضع الاسم الكامل في عمود واحد
            $record->employee->national_id ?? 'غير متوفر',
            $record->project->name ?? 'غير متوفر',
            $record->zone->name ?? 'غير متوفر',
            $record->shift->name ?? 'غير متوفر',
            $record->start_date,
            $record->end_date ?? 'غير محدد',
            $record->status ? 'نشط' : 'غير نشط',
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
        ];
    }
}
