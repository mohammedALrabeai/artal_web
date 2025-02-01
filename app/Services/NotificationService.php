<?php
namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class NotificationService
{
    /**
     * إرسال إشعار إلى مجموعة من المستخدمين.
     *
     * @param array $roles قائمة الأدوار المستهدفة.
     * @param string $title عنوان الإشعار.
     * @param string $body نص الإشعار.
     * @param array $actions قائمة الأزرار للإشعار.
     * @return void
     */
    public function sendNotification(array $roles, string $title, string $body, array $actions): void
    {
        // $users = User::whereHas('role', function ($query) use ($roles) {
        //     $query->whereIn('name', $roles);
    // })->get();
        $users =User::all();
   
        
        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->actions($actions)
            ->sendToDatabase($users); 
    }

    /**
     * إنشاء زر للإشعار.
     *
     * @param string $label النص الظاهر على الزر.
     * @param string $url الرابط الذي يتم توجيه المستخدم إليه.
     * @param string $icon الأيقونة المستخدمة.
     * @return Action
     */
    public function createAction(string $label, string $url, string $icon): Action
    {
        return Action::make(strtolower(str_replace(' ', '_', $label)))
            ->label($label)
            ->url($url)
            ->icon($icon);
    }
}
