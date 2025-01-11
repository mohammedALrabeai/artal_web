<?php

namespace App\Filament\Resources\PrivateLicenseResource\Pages;

use App\Filament\Resources\PrivateLicenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivateLicenses extends ListRecords
{
    protected static string $resource = PrivateLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
