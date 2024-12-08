<?php
namespace App\Filament\Widgets;

use App\Models\Employee;
use Leantony\Charts\Classes\Chart;
use Filament\Widgets\BarChartWidget;

class EmployeeStatistics extends BarChartWidget
{
    protected static ?string $heading = 'Employee Statistics';

    protected function getData(): array
    {
        $activeCount = Employee::where('status', true)->count();
        $inactiveCount = Employee::where('status', false)->count();

        return [
            'datasets' => [
                [
                    'label' => __('Employee Status'),
                    'data' => [$activeCount, $inactiveCount],
                ],
            ],
            'labels' => [
                __('Active Employees'),
                __('Inactive Employees'),
            ],
        ];
    }
}
