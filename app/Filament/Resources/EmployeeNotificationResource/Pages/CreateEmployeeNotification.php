<?php

namespace App\Filament\Resources\EmployeeNotificationResource\Pages;

use App\Filament\Resources\EmployeeNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeNotification extends CreateRecord
{
    protected static string $resource = EmployeeNotificationResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
