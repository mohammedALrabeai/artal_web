<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query = null)
    {
        // إذا لم يتم تمرير استعلام نستخدم الكل
        $this->query = $query
            ? $query->with(['currentZone','latestZone.zone','commercialRecord', 'exclusions' => fn($q) => $q->where('status', 'Approved')->latest()])
            : Employee::with(['currentZone','latestZone.zone','commercialRecord', 'exclusions' => fn($q) => $q->where('status', 'Approved')->latest()])->latest();
    }

    public function collection()
    {
        return $this->query->get();
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
            'اسم الموقع المرشح', // ✅ جديد
            'اسم الموقع الحالي',
            'الحالة',
            'تاريخ الاستبعاد',         // ✅ جديد
            'سبب الاستبعاد',           // ✅ جديد
            'الاشتراك في المؤسسة العامة للتامينات الاجتماعية',
            'السجل التجاري',
            'رقم المشترك'
        ];
    }

    public function map($employee): array
    {
        $exclusion = $employee->exclusions->first(); // أحدث استبعاد Approved

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
            $employee->preferred_zone_name, // اسم الموقع المرشح ✅ جديد
            // optional($employee->currentZone)->name,
            // ✅ صحيح
optional($employee->latestZone?->zone)->name,
 // اسم الموقع الحالي
            $employee->status == 1 ? 'نشط' : 'غير نشط',
            $exclusion?->exclusion_date,       // ✅ جديد
            $exclusion?->reason,               // ✅ جديد
            $employee->insurance_type,
            $employee->commercialRecord?->insurance_number, // السجل التجاري
            $employee->insurance_number, // رقم المشترك

        ];
    }
}
