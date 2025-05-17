<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Exclusion;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeCategoriesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getCards(): array
    {
        $today = Carbon::today('Asia/Riyadh')->toDateString();

        $all = Employee::count();

        $assigned = Employee::whereHas('currentZone')->count();

        $unassigned = Employee::active()
            ->whereDoesntHave('projectRecords')
            ->count();

        $onboarding = Employee::whereHas('currentZone')
            ->whereDoesntHave('attendances', function ($q) {
                $q->where('status', 'present');
            })->count();

        $excluded = Employee::whereHas('exclusions', function ($q) {
            $q->where('status', Exclusion::STATUS_APPROVED);
        })->count();

        $withInsurance = Employee::whereNotNull('commercial_record_id')->count();

        $withoutInsurance = Employee::whereNull('commercial_record_id')->count();

        return [
            // Card::make('إجمالي الموظفين', $all)->color('info'),

            Stat::make('المسندين', $assigned)->color('success'),
            Stat::make('غير المسندين', $unassigned)->color('danger'),

            Stat::make('قيد المباشرة', $onboarding)->color('warning'),

            Stat::make('المستبعدين', $excluded)->color('gray'),

            Stat::make('بـ تأمين', $withInsurance)->color('primary'),
            Stat::make('بدون تأمين', $withoutInsurance)->color('secondary'),
        ];
    }
}
