<?php

namespace App\Filament\Resources\RecordMediaResource\Pages;

use App\Filament\Resources\RecordMediaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordMedia extends ListRecords
{
    protected static string $resource = RecordMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
