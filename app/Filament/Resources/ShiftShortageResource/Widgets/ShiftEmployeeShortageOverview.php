<?php

namespace App\Filament\Resources\ShiftShortageResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class ShiftEmployeeShortageOverview extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $subquery = DB::table('shifts')
            ->join('zones', 'shifts.zone_id', '=', 'zones.id')
            ->join('projects', 'zones.project_id', '=', 'projects.id')
            ->leftJoin('employee_project_records', function ($join) {
                $join->on('shifts.id', '=', 'employee_project_records.shift_id')
                    ->where('employee_project_records.status', '=', 1);
            })
            ->where('shifts.status', 1)
            ->where('zones.status', 1)
            ->where('projects.status', 1)
            ->groupBy('shifts.id', 'shifts.emp_no')
            ->selectRaw('
                shifts.id,
                shifts.emp_no,
                COUNT(employee_project_records.id) as assigned_count
            ');

        $totalShortage = DB::table(DB::raw("({$subquery->toSql()}) as sub"))
            ->mergeBindings($subquery) // ضروري عشان يربط الباراميترز صح
            ->selectRaw('SUM(CASE WHEN emp_no - assigned_count > 0 THEN emp_no - assigned_count ELSE 0 END) as total_shortage')
            ->value('total_shortage') ?? 0;

        return [
            Card::make('إجمالي نقص الموظفين في جميع الورديات النشطة', $totalShortage)
                ->color($totalShortage > 0 ? 'danger' : 'success')
                ->description($totalShortage > 0 ? 'هناك نقص في الموظفين يجب تغطيته!' : 'كل الورديات مكتملة ✅'),
        ];
    }
}
