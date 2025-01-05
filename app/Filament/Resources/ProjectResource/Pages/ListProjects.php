<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProjectResource;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use Illuminate\Support\Facades\URL;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make(),

            Actions\Action::make('exportProjectsZonesReport')
    ->label('Export Projects Zones Report')
    ->form([
        Forms\Components\DatePicker::make('start_date')
            ->label('Start Date')
            ->required(),
        Forms\Components\DatePicker::make('end_date')
            ->label('End Date')
            ->required(),
    ])
    ->action(function (array $data) {
        $url = URL::temporarySignedRoute(
            'export.projects.zones.report',
            now()->addMinutes(5),
            [
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]
        );

        return redirect($url);
    }),

        ];
    }
}
