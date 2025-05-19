<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class AttendancePieStatsWidget extends ChartWidget
{
    protected static ?string $heading   = 'إحصائيات الحضور اليومية';
    protected static ?int    $sort      = 0;
    protected static string   $color     = 'info';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $today = Carbon::today('Asia/Riyadh')->toDateString();

        // 1) جلب البيانات من الكاش
        // ------------------------------------------
        // يحتوي على هيكل: [ [ 'projects' => [ 'zones' => [ 'shifts' => [ ['is_current_shift', 'attendees_count', ...] ] ] ] ] ]
        $summary     = Cache::get('active_shifts_summary', []);

        // مصفوفة المفقودين: كل عنصر فيها ['employee_ids' => [...]]
        $missingMap  = Cache::get("missing_employees_summary_{$today}", []);

        // 2) احسب الحضور والتغطيات من الـ summary
        // ------------------------------------------
        $totalPresent  = 0;
        $totalCoverage = 0;

        foreach ($summary as $area) {
            foreach ($area['projects'] as $project) {
                foreach ($project['zones'] as $zone) {
                    // تغطيات المنطقة (مخزنة مباشرة)
                    $totalCoverage += $zone['active_coverages_count'] ?? 0;

                    // لكل الوردات بالحلقة
                    foreach ($zone['shifts'] as $shift) {
                        if (! empty($shift['is_current_shift'])) {
                            $totalPresent += $shift['attendees_count'] ?? 0;
                        }
                    }
                }
            }
        }

        // 3) احسب عدد الغياب من مصفوفة الـ missingMap
        // ------------------------------------------
        $totalAbsent = 0;
        foreach ($missingMap as $entry) {
            $totalAbsent += count($entry['employee_ids'] ?? []);
        }

        // 4) إجمالي الموظفين (يمكنك أيضاً تخزينه في كاش منفصل)
        // ------------------------------------------
        $totalEmployees = Cache::get('total_employees_count')
            ?? Employee::where('status', 1)->count();

        // 5) أعد البيانات بالشكل المطلوب للـ pie chart
        // ------------------------------------------
        return [
            'datasets' => [
                [
                    'label' => 'الموظفين',
                    'data'  => [
                        $totalPresent,
                        $totalCoverage,
                        $totalAbsent,
                    ],
                    'backgroundColor' => [
                        '#10B981', // حاضر
                        '#3B82F6', // تغطية
                        '#EF4444', // غياب
                    ],
                ],
            ],
            'labels' => [
                'حاضر',
                'تغطية',
                'غياب',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
