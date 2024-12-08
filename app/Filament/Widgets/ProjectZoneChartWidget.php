<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\PieChartWidget;

class ProjectZoneChartWidget extends PieChartWidget
{
    protected static ?string $heading = 'Projects & Zones Chart';

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
