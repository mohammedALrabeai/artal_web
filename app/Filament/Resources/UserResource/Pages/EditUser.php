<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\UserResource;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Actions; // التأكد من استيراد Action الصحيح

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Notification::make()
        //     ->title('Changes saved!')
        //     ->success()
        //     ->body('Your changes have been saved!')
        //     ->send()
        //     ->sendToDatabase(auth()->user());

        $users = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['manager', 'general_manager', 'hr']); // الأدوار المطلوبة
        })->get(); // مثال: إرسال الإشعار للمسؤولين والمدراء


            Notification::make()
            ->title('Changes saved!')
            ->success()
            ->body('Your changes have been saved!')
            ->actions([
                Actions\Action::make('view')
                    ->label('View User')
                    ->url("/admin/users/{$this->record->id}/edit")                    // رابط تحرير المستخدم الحالي
                    ->icon('heroicon-s-eye'),
                Actions\Action::make('back')
                    ->label('Back to List')
                    ->url("/admin/users") // رابط قائمة المستخدمين
                    ->icon('heroicon-s-arrow-left'),
            ])
            ->send()
            ->sendToDatabase($users);



// for($i = 0; $i < 100; $i++) {
//     Notification::make()
//     ->title('Changes saved!')
//     ->success()
//     ->body('Your changes have been saved!')
//     ->actions([
//         Actions\Action::make('view')
//             ->label('View User')
//             ->url("/admin/users/{$this->record->id}/edit")                    // رابط تحرير المستخدم الحالي
//             ->icon('heroicon-s-eye'),
//         Actions\Action::make('back')
//             ->label('Back to List')
//             ->url("/admin/users") // رابط قائمة المستخدمين
//             ->icon('heroicon-s-arrow-left'),
//     ])
//     // ->send()
//     ->sendToDatabase(auth()->user());
// }

//             $recipient = auth()->user();
 
// $recipient->notify(
//     Notification::make()
//         ->title('Saved successfully')
//         ->toDatabase());

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
}
