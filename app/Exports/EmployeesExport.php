<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Employee::with('currentZone')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'الاسم',
            'الاسم بالإنجليزي',
            'الجنسية',
            'رقم الهوية',
            'تاريخ انتهاء الهوية',
            'تاريخ الميلاد',
            'فصيلة الدم',
            'الحالة الاجتماعية',
            'الراتب الأساسي',
            'بدل السكن',
            'بدلات أخرى',
            'المسمى الوظيفي',
            'رقم الأيبان',
            'الحالة الوظيفية',
            'اسم البنك',
            'التأمين الطبي',
            'اسم شركة التأمين',
            'نهاية التأمين',
            'رقم الجوال',
            'رقم إضافي',
            'البريد الإلكتروني',
            'تاريخ المباشرة',
            'تاريخ الإضافة',
            'المؤهل',
            'مكان الميلاد',
            'رقم المشترك بالتأمينات',
            'اسم الموقع الحالي',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->name,
            $employee->english_name,
            $employee->nationality,
            $employee->national_id,
            $employee->national_id_expiry,
            $employee->birth_date,
            $employee->blood_type,
            $employee->marital_status,
            $employee->basic_salary,
            $employee->living_allowance,
            $employee->other_allowances,
            $employee->job_title,
            $employee->bank_account,
            $employee->job_status,
            $employee->bank_name,
            $employee->health_insurance_status,
            $employee->health_insurance_company,
            $employee->insurance_end_date,
            $employee->mobile_number,
            $employee->phone_number,
            $employee->email,
            $employee->actual_start,
            $employee->contract_start,
            $employee->qualification,
            $employee->birth_place,
            $employee->insurance_number,
            optional($employee->currentZone)->name,
        ];
    }
}
