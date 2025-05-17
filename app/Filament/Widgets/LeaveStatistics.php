<?php
namespace App\Filament\Widgets;

use App\Models\Leave;
use Filament\Widgets\LineChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class LeaveStatistics extends LineChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Leave Statistics';
     protected static ?int $sort = 5;

    protected function getData(): array
    {
        $leaves = Leave::selectRaw('MONTH(start_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('Leaves Taken'),
                    'data' => $leaves->pluck('count')->toArray(),
                ],
            ],
            'labels' => $leaves->pluck('month')->map(fn ($month) => date('F', mktime(0, 0, 0, $month, 1)))->toArray(),
        ];
    }
}
