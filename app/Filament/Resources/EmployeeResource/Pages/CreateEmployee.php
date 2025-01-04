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
       // جلب المستخدمين الذين لديهم الأدوار المطلوبة عبر العلاقة مع جدول الأدوار
    $managers = User::whereHas('role', function ($query) {
        $query->whereIn('name', ['manager', 'general_manager', 'hr']); // الأدوار المطلوبة
    })->get();

    // إرسال الإشعارات
    foreach ($managers as $manager) {
        $manager->notify(new NewEmployeeNotification($this->record));
    }
    }
}
