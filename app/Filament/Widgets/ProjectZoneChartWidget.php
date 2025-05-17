<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\PieChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ProjectZoneChartWidget extends PieChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Projects & Zones Chart';
     protected static ?int $sort = 5;

    protected function getData(): array
    {
        $projects = Project::withCount('zones')->get();

        return [
            'labels' => $projects->pluck('name'),
            'datasets' => [
                [
                    'label' => __('Zones per Project'),
                    'data' => $projects->pluck('zones_count'),
                    'backgroundColor' => [
                        '#42A5F5', '#66BB6A', '#FFA726', '#EF5350', '#AB47BC'
                    ],
                ],
            ],
        ];
    }
}
