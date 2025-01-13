<?php

namespace App\Filament\Resources\EmployeeNotificationResource\Pages;

use App\Filament\Resources\EmployeeNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeNotification extends EditRecord
{
    protected static string $resource = EmployeeNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
