<?php

namespace App\Filament\Widgets;

use App\Models\Zone;
use App\Models\Project;
use Filament\Widgets\Widget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ProjectZoneReportWidget extends Widget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Project & Zone Reports';

    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.project-zone-report-widget';

    public function getViewData(): array
    {
        return [
            'project_count' => Project::count(),
            'zone_count' => Zone::count(),
            'project_data' => Project::withCount('zones')->get(),
        ];
    }
}
