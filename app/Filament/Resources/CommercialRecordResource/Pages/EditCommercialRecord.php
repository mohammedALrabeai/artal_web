<?php

namespace App\Filament\Resources\CommercialRecordResource\Pages;

use App\Filament\Resources\CommercialRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommercialRecord extends EditRecord
{
    protected static string $resource = CommercialRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
