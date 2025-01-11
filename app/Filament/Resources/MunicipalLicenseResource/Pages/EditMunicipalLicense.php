<?php

namespace App\Filament\Resources\MunicipalLicenseResource\Pages;

use App\Filament\Resources\MunicipalLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMunicipalLicense extends EditRecord
{
    protected static string $resource = MunicipalLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
