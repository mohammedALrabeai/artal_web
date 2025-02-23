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
                ->description(__('Employees currently active'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            // ✅ الموظفون المستبعدون
            Card::make(__('Excluded Employees'), $this->getExcludedEmployeesCount())
                ->description(__('Employees who are currently excluded'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }

    /**
     * 🔍 حساب عدد الموظفين النشطين
     */
    private function getActiveEmployeesCount(): int
    {
        return Employee::where('status', true)
            ->where(function ($query) {
                $query->whereNull('contract_end') // لا يوجد نهاية عقد
                    ->orWhere('contract_end', '>', now()); // أو العقد لم ينتهِ
            })
            ->whereDoesntHave('exclusions', function ($query) {
                $query->where('status', Exclusion::STATUS_APPROVED)
                    ->where('exclusion_date', '<=', now());
            })
            ->count();
    }

    /**
     * ❌ حساب عدد الموظفين المستبعدين
     */
    private function getExcludedEmployeesCount(): int
    {
        return Employee::whereHas('exclusions', function ($query) {
            $query->where('status', Exclusion::STATUS_APPROVED)
                ->where('exclusion_date', '<=', now());
        })
            ->count();
    }
}
