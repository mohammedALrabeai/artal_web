<?php

namespace App\Filament\Resources\BankResource\Pages;

use Filament\Actions;
use App\Services\NotificationService;
use App\Filament\Resources\BankResource;
use Filament\Resources\Pages\EditRecord;

class EditBank extends EditRecord
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'تم تعديل بيانات البنك!', // عنوان الإشعار
            'تم تعديل بيانات البنك بنجاح!', // نص الإشعار
            [
                $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}/edit", 'heroicon-s-eye'),
                $notificationService->createAction('Back to List', '/admin/banks', 'heroicon-s-arrow-left'),
            ]
        );

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
}
