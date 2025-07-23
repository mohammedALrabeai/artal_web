<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Exclusion;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class EmployeeCategoriesStatsWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

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
            ->whereDoesntHave('attendances', fn ($q) => $q->where('status', 'present'))
            ->count();

        $excluded = Employee::whereHas('exclusions', fn ($q) =>
            $q->where('status', Exclusion::STATUS_APPROVED))->count();

        $withInsurance = Employee::whereNotNull('commercial_record_id')->count();
        $withoutInsurance = Employee::whereNull('commercial_record_id')->count();

        return [
            Card::make('المسندين', $assigned)->color('success'),
            Card::make('غير المسندين', $unassigned)->color('danger'),
            Card::make('قيد المباشرة', $onboarding)->color('warning'),
            Card::make('المستبعدين', $excluded)->color('gray'),
            Card::make('بـ تأمين', $withInsurance)->color('primary'),
            Card::make('بدون تأمين', $withoutInsurance)->color('secondary'),
        ];
    }
}
