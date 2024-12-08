<?php

namespace App\Filament\Resources\EmployeeZoneResource\Pages;

use App\Filament\Resources\EmployeeZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeZones extends ListRecords
{
    protected static string $resource = EmployeeZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
