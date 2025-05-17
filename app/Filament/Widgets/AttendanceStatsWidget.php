<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AttendanceStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.attendance-stats-widget';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $today = now()->toDateString();

        $totalEmployees = \App\Models\Employee::where('status', 1)->count();

        $present = \App\Models\Attendance::whereDate('date', $today)
            ->where('status', 'present')
            ->where('is_coverage', false)
            ->distinct('employee_id')
            ->count('employee_id');

        $coverages = \App\Models\Attendance::whereDate('date', $today)
            ->where('is_coverage', true)
            ->distinct('employee_id')
            ->count('employee_id');

        $off = \App\Models\Attendance::whereDate('date', $today)
            ->where('status', 'off')
            ->distinct('employee_id')
            ->count('employee_id');

        $absent = $totalEmployees - ($present + $coverages + $off);

        return [
            'total' => $totalEmployees,
            'present' => $present,
            'coverage' => $coverages,
            'off' => $off,
            'absent' => max(0, $absent),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }
}
