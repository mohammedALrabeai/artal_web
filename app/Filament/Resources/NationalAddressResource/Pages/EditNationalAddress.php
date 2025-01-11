<?php

namespace App\Filament\Resources\NationalAddressResource\Pages;

use App\Filament\Resources\NationalAddressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNationalAddress extends EditRecord
{
    protected static string $resource = NationalAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
