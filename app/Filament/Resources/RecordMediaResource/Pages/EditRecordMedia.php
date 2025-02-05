<?php

namespace App\Filament\Resources\RecordMediaResource\Pages;

use App\Filament\Resources\RecordMediaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecordMedia extends EditRecord
{
    protected static string $resource = RecordMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
