<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\NotificationService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord; // التأكد من استيراد Action الصحيح

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    // {

    //     $notificationService = new NotificationService;
    //     $notificationService->sendNotification(
    //         ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
    //         'Changes saved!', // عنوان الإشعار
    //         'Your changes have been saved!', // نص الإشعار
    //         [
    //             $notificationService->createAction('View User', "/admin/users/{$this->record->id}/edit", 'heroicon-s-eye'),
    //             $notificationService->createAction('Back to List', '/admin/users', 'heroicon-s-arrow-left'),
    //         ]
    //     );

    //     // $users = User::whereHas('role', function ($query) {
    //     //     $query->whereIn('name', ['manager', 'general_manager', 'hr']); // الأدوار المطلوبة
    //     // })->get(); // مثال: إرسال الإشعار للمسؤولين والمدراء

    //     //     Notification::make()
    //     //     ->title('Changes saved!')
    //     //     ->success()
    //     //     ->body('Your changes have been saved!')
    //     //     ->actions([
    //     //         Actions\Action::make('view')
    //     //             ->label('View User')
    //     //             ->url("/admin/users/{$this->record->id}/edit")                    // رابط تحرير المستخدم الحالي
    //     //             ->icon('heroicon-s-eye'),
    //     //         Actions\Action::make('back')
    //     //             ->label('Back to List')
    //     //             ->url("/admin/users") // رابط قائمة المستخدمين
    //     //             ->icon('heroicon-s-arrow-left'),
    //     //     ])
    //     //     ->send()
    //     //     ->sendToDatabase($users);

    //     parent::save($shouldRedirect, $shouldSendSavedNotification);
    // }
}
