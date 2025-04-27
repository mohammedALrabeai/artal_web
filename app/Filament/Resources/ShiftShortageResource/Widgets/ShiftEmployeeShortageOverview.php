<?php

namespace App\Filament\Resources\ShiftShortageResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class ShiftEmployeeShortageOverview extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        // اعمل نفس فلترة الجدول
        $subquery = DB::table('shifts')
            ->join('zones', function ($join) {
                $join->on('shifts.zone_id', '=', 'zones.id')
                    ->where('zones.status', '=', 1); // الموقع نشط
            })
            ->join('projects', function ($join) {
                $join->on('zones.project_id', '=', 'projects.id')
                    ->where('projects.status', '=', 1); // المشروع نشط
            })
            ->leftJoin('employee_project_records', function ($join) {
                $join->on('shifts.id', '=', 'employee_project_records.shift_id')
                    ->where('employee_project_records.status', '=', 1); // الموظفين المعينين فقط
            })
            ->where('shifts.status', '=', 1) // الوردية نشطة
            ->groupBy('shifts.id', 'shifts.emp_no')
            ->selectRaw('
                shifts.id,
                shifts.emp_no,
                COUNT(employee_project_records.id) as assigned_count
            ');

        // نحسب مجموع النقص مثل الجدول بالضبط
        $totalShortage = DB::table(DB::raw("({$subquery->toSql()}) as sub"))
            ->mergeBindings($subquery)
            ->selectRaw('SUM(CASE WHEN emp_no > assigned_count THEN emp_no - assigned_count ELSE 0 END) as total_shortage')
            ->value('total_shortage') ?? 0;

        return [
            Card::make('إجمالي نقص الموظفين في الورديات النشطة بالمواقع والمشاريع النشطة', $totalShortage)
                ->color($totalShortage > 0 ? 'danger' : 'success')
                ->description($totalShortage > 0 ? 'هناك نقص يجب تغطيته!' : 'كل الورديات مكتملة ✅'),
        ];
    }
}
