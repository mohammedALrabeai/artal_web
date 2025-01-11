<?php

namespace App\Filament\Resources\MunicipalLicenseResource\Pages;

use App\Filament\Resources\MunicipalLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMunicipalLicenses extends ListRecords
{
    protected static string $resource = MunicipalLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
