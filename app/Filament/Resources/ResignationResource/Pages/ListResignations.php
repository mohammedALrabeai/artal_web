<?php

namespace App\Filament\Resources\ResignationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ResignationResource;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListResignations extends ListRecords
{
    protected static string $resource = ResignationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make(),
        ];
    }
}
