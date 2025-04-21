<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Exclusion;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class EmployeeReportWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected function getCards(): array
    {
        return [
            // ✅ إجمالي عدد الموظفين
            Card::make(__('Total Employees'), Employee::count())
                ->description(__('Number of all registered employees'))
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            // ✅ الموظفون النشطون
            Card::make(__('Active Employees'), $this->getActiveEmployeesCount())
                ->description(__(
                    'Ended: :ended | Excluded: :excluded',
                    [
                        'ended' => $this->getEndedContractCount(),
                        'excluded' => $this->getActiveExcludedCount(),
                    ]
                ))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            // ❌ الموظفون غير النشطين
            Card::make(__('Inactive Employees'), $this->getInactiveEmployeesCount())
                ->description(__(
                    'Excluded: :excluded',
                    [
                        'excluded' => $this->getInactiveExcludedCount(),
                    ]
                ))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }

    private function getActiveEmployeesCount(): int
    {
        return Employee::where('status', true)->count();
    }

    private function getEndedContractCount(): int
    {
        return Employee::where('status', true)
            ->whereNotNull('contract_end')
            ->where('contract_end', '<=', now())
            ->count();
    }

    private function getActiveExcludedCount(): int
    {
        return Employee::where('status', true)
            ->whereHas('exclusions', function ($query) {
                $query->where('status', Exclusion::STATUS_APPROVED)
                    ->where('exclusion_date', '<=', now());
            })
            ->count();
    }

    private function getInactiveEmployeesCount(): int
    {
        return Employee::where('status', false)->count();
    }

    private function getInactiveExcludedCount(): int
    {
        return Employee::where('status', false)
            ->whereHas('exclusions', function ($query) {
                $query->where('status', Exclusion::STATUS_APPROVED)
                    ->where('exclusion_date', '<=', now());
            })
            ->count();
    }
}
