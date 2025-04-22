<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsecutiveAbsenceExport implements FromCollection, WithHeadings
{
    public Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'الاسم الكامل',
            'رقم المعرف',
            'رقم الهوية',
            'رقم الجوال',
            'اسم المشروع',
            'اسم الموقع',
            'اسم الوردية',
            'عدد أيام الغياب',
            'آخر تاريخ غياب',
        ];
    }
}
