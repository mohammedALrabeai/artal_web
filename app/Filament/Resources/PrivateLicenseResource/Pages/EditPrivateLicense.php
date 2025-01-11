<?php

namespace App\Filament\Resources\PrivateLicenseResource\Pages;

use App\Filament\Resources\PrivateLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateLicense extends EditRecord
{
    protected static string $resource = PrivateLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
