<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use App\Services\NotificationService;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProjectResource;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {

        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'تم اضافة مشروع جديدة ', // عنوان الإشعار
            $this->record->id.'  | '. $this->record->name.'  | '.auth()->user()->name, // نص الإشعار
            [
                // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة المشاريع', '/admin/projects', 'heroicon-s-eye'),
            ]
        );



  
    }
}
