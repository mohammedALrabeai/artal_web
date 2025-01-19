<?php

namespace App\Filament\Resources\AreaResource\Pages;

use Filament\Actions;
use App\Services\NotificationService;
use App\Filament\Resources\AreaResource;
use Filament\Resources\Pages\EditRecord;

class EditArea extends EditRecord
{
    protected static string $resource = AreaResource::class;

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
            'تم تعديل بيانات المنطقة!', // عنوان الإشعار
            $this->record->name.'  | '.auth()->user()->name, // نص الإشعار
            [
                // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}/edit", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة المناطق', '/admin/areas', 'heroicon-s-arrow-left'),
            ]
        );

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }


}
