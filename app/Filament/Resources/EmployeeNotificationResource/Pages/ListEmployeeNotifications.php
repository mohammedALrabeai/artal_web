<?php

namespace App\Filament\Resources\EmployeeNotificationResource\Pages;

use App\Filament\Resources\EmployeeNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeNotifications extends ListRecords
{
    protected static string $resource = EmployeeNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
