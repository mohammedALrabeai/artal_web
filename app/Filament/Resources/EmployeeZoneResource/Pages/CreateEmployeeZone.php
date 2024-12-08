<?php

namespace App\Filament\Resources\EmployeeZoneResource\Pages;

use App\Filament\Resources\EmployeeZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeZone extends CreateRecord
{
    protected static string $resource = EmployeeZoneResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = auth()->id();
        return $data;
    }

}
