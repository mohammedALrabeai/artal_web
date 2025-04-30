<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsecutiveAbsenceExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        return $this->data;
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
            'آخر تاريخ حضور',
        ];
    }
}
