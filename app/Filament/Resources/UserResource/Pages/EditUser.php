<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        Notification::make()
            ->title('Changes saved!')
            ->success()
            ->body('Your changes have been saved!')
            ->send()
            ->sendToDatabase(auth()->user());
//             $recipient = auth()->user();
 
// $recipient->notify(
//     Notification::make()
//         ->title('Saved successfully')
//         ->toDatabase());

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
}
