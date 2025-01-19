<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use App\Services\NotificationService;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProjectResource;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

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
            'تم تعديل بيانات المشروع!', // عنوان الإشعار
            $this->record->id.'  | '.   $this->record->name.'  | '.auth()->user()->name, // نص الإشعار
            [
                // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}/edit", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة المشاريع', '/admin/projects', 'heroicon-s-arrow-left'),
            ] 
        );

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    
}
