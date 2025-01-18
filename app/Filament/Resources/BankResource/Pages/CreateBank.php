<?php

namespace App\Filament\Resources\BankResource\Pages;

use Filament\Actions;
use App\Services\NotificationService;
use App\Filament\Resources\BankResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBank extends CreateRecord
{
    protected static string $resource = BankResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // إضافة السجلات اللازمة بعد إنشاء البنك

        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'اضافة بنك جديد', // عنوان الإشعار
            'تم اضافة بنك جديد بنجاح!', // نص الإشعار
            [
                // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة البنوك', '/admin/banks', 'heroicon-s-eye'),
            ]
        );
    }

}
