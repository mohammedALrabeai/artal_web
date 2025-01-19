<?php

namespace App\Filament\Resources\NationalAddressResource\Pages;

use App\Filament\Resources\NationalAddressResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNationalAddress extends CreateRecord
{
    protected static string $resource = NationalAddressResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
