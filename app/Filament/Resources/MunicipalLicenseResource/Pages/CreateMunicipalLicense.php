<?php

namespace App\Filament\Resources\MunicipalLicenseResource\Pages;

use App\Filament\Resources\MunicipalLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMunicipalLicense extends CreateRecord
{
    protected static string $resource = MunicipalLicenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
