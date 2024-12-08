<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeProjectRecord extends EditRecord
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
