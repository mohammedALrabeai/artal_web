<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendancePieStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'إحصائيات الحضور اليومية';

    protected static ?int $sort = 1;

    protected static string $color = 'info';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $today = Carbon::today('Asia/Riyadh')->toDateString();

        $totalEmployees = Employee::where('status', 1)->count();

        // عدّ الحالات المختلفة من الحضور
        $attendanceCounts = Attendance::query()
            ->whereDate('date', $today)
            ->selectRaw("
        COUNT(DISTINCT CASE WHEN status = 'present' AND is_coverage = 0 THEN employee_id END) as present,
        COUNT(DISTINCT CASE WHEN status = 'coverage' THEN employee_id END) as coverage,
        COUNT(DISTINCT CASE WHEN status = 'off' THEN employee_id END) as off,
        COUNT(DISTINCT CASE WHEN status = 'leave' THEN employee_id END) as `leave`,
        COUNT(DISTINCT CASE WHEN status = 'UV' THEN employee_id END) as unpaid,
        COUNT(DISTINCT CASE WHEN status = 'M' THEN employee_id END) as morbid
    ")
            ->first();

        $counted = collect($attendanceCounts)->map(fn ($v) => (int) $v);
        $counted['absent'] = max(0, $totalEmployees - $counted->sum());

        return [
            'datasets' => [
                [
                    'label' => 'عدد الموظفين',
                    'data' => [
                        $counted['present'],
                        $counted['coverage'],
                        $counted['off'],
                        $counted['leave'],
                        $counted['unpaid'],
                        $counted['morbid'],
                        $counted['absent'],
                    ],
                    'backgroundColor' => [
                        '#10B981', // حاضر
                        '#3B82F6', // تغطية
                        '#FACC15', // أوف
                        '#8B5CF6', // إجازة
                        '#EC4899', // بدون راتب
                        '#F97316', // مرضي
                        '#EF4444', // غياب
                    ],
                ],
            ],
            'labels' => [
                'حاضر',
                'تغطية',
                'أوف',
                'إجازة',
                'بدون راتب',
                'مرضي',
                'غياب',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
