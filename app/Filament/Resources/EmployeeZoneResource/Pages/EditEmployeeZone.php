<?php

namespace App\Filament\Resources\EmployeeZoneResource\Pages;

use App\Filament\Resources\EmployeeZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeZone extends EditRecord
{
    protected static string $resource = EmployeeZoneResource::class;



    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
