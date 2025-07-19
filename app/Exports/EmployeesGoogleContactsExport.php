<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesGoogleContactsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Employee::select(
            'first_name',
            'father_name',
            'grandfather_name',
            'family_name',
            'mobile_number'
        )->get();
    }

   public function headings(): array
{
    return [
        'Name',
        'Phone 1 - Value',
        'Phone 1 - Type',
        'Phone 1 - Label',
    ];
}


   public function map($employee): array
{
    return [
        $employee->name,
        $this->formatPhone($employee->mobile_number),
        'Mobile',
        'Mobile',
    ];
}


   private function formatPhone($phone)
{
    // إزالة أي رموز أو مسافات
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (str_starts_with($phone, '9665')) {
        // ✅ الرقم يبدأ بـ9665 → فقط نضيف "+"
        return '+' . $phone;
    }

    if (str_starts_with($phone, '05')) {
        // ✅ الرقم يبدأ بـ05 → نحذف 0 ونضيف +966
        return '+966' . substr($phone, 1);
    }

    if (str_starts_with($phone, '5')) {
        // ✅ الرقم يبدأ بـ5 مباشرة → نضيف +966
        return '+966' . $phone;
    }

    if (!str_starts_with($phone, '+')) {
        // ✅ أي رقم غير مبدوء بـ "+" → نفترض أنه محلي ونضيف +966
        return '+966' . $phone;
    }

    return $phone;
}

}
