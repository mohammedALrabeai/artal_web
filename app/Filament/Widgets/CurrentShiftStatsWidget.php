<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\Cache;

class CurrentShiftStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '120s';

    protected function getCards(): array
    {
        $now = now('Asia/Riyadh');
        // $intervalSeconds = 60;
        // $intervalKey = floor($now->timestamp / $intervalSeconds);
        $cacheKey = 'active_shifts_summary';
        $summary = Cache::get($cacheKey, []);

        $required = $present = $coverage = $leftToday = 0;

        foreach ($summary as $area) {
            foreach ($area['projects'] as $project) {
                foreach ($project['zones'] as $zone) {
                    $coverage += $zone['active_coverages_count'] ?? 0;
                    foreach ($zone['shifts'] as $shift) {
                        if ($shift['is_current_shift']) {
                            $required += $shift['emp_no'];
                            $present += $shift['attendees_count'];
                        }
                    }
                }
            }
        }

        // نحسب عدد المنصرفين اليوم من جدول الحضور
        $leftToday = \App\Models\Attendance::query()
            ->whereDate('date', $now->toDateString())
            ->whereNotNull('check_out')
            ->distinct('employee_id')
            ->count('employee_id');

        $remaining = max(0, $required - $present - $coverage);

        return [
            Card::make('عدد الموظفين المطلوبين حاليًا', $required),
            Card::make('عدد الحاضرين حاليًا', $present)->color('success'),
            Card::make('عدد التغطيات', $coverage)->color('info'),
            Card::make('النقص الحالي', $remaining)->color($remaining > 0 ? 'danger' : 'success'),
            Card::make('المنصرفين اليوم', $leftToday)->color('gray'),
        ];
    }
}
