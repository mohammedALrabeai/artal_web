<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class EmployeeReportWidget extends StatsOverviewWidget
{
    use HasWidgetShield;
    protected function getCards(): array
    {
        return [
            Card::make(__('Total Employees'), \App\Models\Employee::count())
                ->description(__('Number of all registered employees'))
                ->descriptionIcon('heroicon-o-users'),

            Card::make(__('Active Employees'), \App\Models\Employee::where('status', 'active')->count())
                ->description(__('Employees currently active'))
                ->descriptionIcon('heroicon-o-check-circle'),

            Card::make(__('Absent Employees'), \App\Models\Employee::where('status', 'absent')->count())
                ->description(__('Employees currently absent'))
                ->descriptionIcon('heroicon-o-x-circle'),
        ];
    }
}
