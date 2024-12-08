<?php
namespace App\Filament\Widgets;

use App\Models\Leave;
use Filament\Widgets\LineChartWidget;

class LeaveStatistics extends LineChartWidget
{
    protected static ?string $heading = 'Leave Statistics';

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
