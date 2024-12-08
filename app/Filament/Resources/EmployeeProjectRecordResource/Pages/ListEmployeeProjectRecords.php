<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeProjectRecords extends ListRecords
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
