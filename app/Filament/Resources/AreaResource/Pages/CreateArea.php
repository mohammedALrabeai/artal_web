<?php
namespace App\Filament\Resources\AreaResource\Pages;

use App\Services\NotificationService;
use App\Filament\Resources\AreaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User; // لاستدعاء المستخدمين
use Illuminate\Support\Facades\Notification; // موجه إشعارات Laravel
use App\Notifications\AreaCreatedNotification; // الكلاس الخاص بالإشعار

class CreateArea extends CreateRecord
{
    protected static string $resource = AreaResource::class;

    protected function afterCreate(): void
    {
//         // جلب المستخدمين الأدمن فقط
//         $admins = User::where('role', 'manager')->get(); // افترض أن لديك عمود "role" في جدول المستخدمين
// // $alluser=User::all();
//         // إرسال الإشعارات إلى قاعدة البيانات
//         Notification::send($admins, new AreaCreatedNotification($this->record));
  
        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'تم اضافة منطقة جديدة ', // عنوان الإشعار
            $this->record->name.'  | '.auth()->user()->name, // نص الإشعار
            [
                // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة المناطق', '/admin/areas', 'heroicon-s-eye'),
            ]
        );



  
    }
}
