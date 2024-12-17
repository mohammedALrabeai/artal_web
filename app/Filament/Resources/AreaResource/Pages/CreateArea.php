<?php
namespace App\Filament\Resources\AreaResource\Pages;

use App\Filament\Resources\AreaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User; // لاستدعاء المستخدمين
use App\Notifications\AreaCreatedNotification; // الكلاس الخاص بالإشعار
use Illuminate\Support\Facades\Notification; // موجه إشعارات Laravel

class CreateArea extends CreateRecord
{
    protected static string $resource = AreaResource::class;

    protected function afterCreate(): void
    {
        // جلب المستخدمين الأدمن فقط
        $admins = User::where('role', 'manager')->get(); // افترض أن لديك عمود "role" في جدول المستخدمين
// $alluser=User::all();
        // إرسال الإشعارات إلى قاعدة البيانات
        Notification::send($admins, new AreaCreatedNotification($this->record));
    }
}
