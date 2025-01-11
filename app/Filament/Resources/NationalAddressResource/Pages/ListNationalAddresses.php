<?php

namespace App\Filament\Resources\NationalAddressResource\Pages;

use App\Filament\Resources\NationalAddressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNationalAddresses extends ListRecords
{
    protected static string $resource = NationalAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
