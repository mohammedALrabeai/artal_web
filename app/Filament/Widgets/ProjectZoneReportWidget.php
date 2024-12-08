<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Zone;
use Filament\Widgets\Widget;

class ProjectZoneReportWidget extends Widget
{
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
