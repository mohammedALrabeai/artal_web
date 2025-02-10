<?php

namespace App\Filament\Resources\ZoneResource\Pages;

use App\Filament\Resources\ZoneResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewZone extends ViewRecord
{
    protected static string $resource = ZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->disabled(),

            TextInput::make('pattern.name')
                ->label(__('Pattern'))
                ->disabled(),

            TextInput::make('project.name')
                ->label(__('Project'))
                ->disabled(),

            TextInput::make('start_date')
                ->label(__('Start Date'))
                ->disabled(),

            TextInput::make('area')
                ->label(__('Range (meter)'))
                ->disabled(),

            TextInput::make('emp_no')
                ->label(__('Number of Employees'))
                ->disabled(),

            Toggle::make('status')
                ->label(__('Active'))
                ->disabled(),

            // Forms\Components\View::make('components.map-viewer')
            //     ->label(__('Location'))
            //     ->columnSpanFull(),
        ];
    }
}
