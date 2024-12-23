<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Notifications\NewEmployeeNotification;
use App\Models\User;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // إرسال إشعار لجميع المستخدمين الذين لديهم دور مدير
        $managers = User::whereIn('role', ['manager', 'general_manager', 'hr'])->get();
        
        foreach ($managers as $manager) {
            $manager->notify(new NewEmployeeNotification($this->record));
        }
    }
}
