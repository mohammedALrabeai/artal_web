<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShiftShortagesExport implements FromCollection, WithHeadings
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query
            ->with(['zone.project.area']) // تحميل العلاقات المطلوبة
            ->get()
            ->map(function ($shift) {
                $assignedEmployees = $shift->employeeProjectRecords()->where('status', 1)->count();
                $absentEmployees = $shift->attendances()->where('status', 'absent')->whereDate('date', today())->count();
                $coverageEmployees = $shift->attendances()->where('status', 'coverage')->whereDate('date', today())->count();
                $shortage = max(0, $shift->emp_no - $assignedEmployees);

                return [
                    $shift->zone?->project?->area?->name ?? '-',     // اسم المنطقة
                    $shift->zone?->project?->name ?? '-',             // اسم المشروع
                    $shift->zone?->name ?? '-',                       // اسم الموقع
                    $shift->name ?? '-',                              // اسم الوردية
                    $shift->emp_no ?? 0,                              // الموظفين المطلوبين
                    $assignedEmployees ?? 0,                          // الموظفين الحاليين
                    $shortage,                                         // النقص
                    $shift->shortage_days_count ?? 0,                 // عدد أيام النقص
                    $absentEmployees ?? 0,                             // عدد الغياب
                    $coverageEmployees ?? 0,                           // عدد المغطيين
                ];
            });
    }

    public function headings(): array
    {
        return [
            'المنطقة',
            'المشروع',
            'الموقع',
            'الوردية',
            'الموظفين المطلوبين',
            'الموظفين الحاليين',
            'النقص',
            'عدد ايام النقص',
            'عدد الغياب',
            'عدد المغطيين',
        ];
    }
}
