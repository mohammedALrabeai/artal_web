<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Services\NotificationService;
use Filament\Resources\Pages\CreateRecord;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // بعد إنشاء السجل يتم إرسال إشعار يحتوي على تفاصيل الوردية
    // protected function afterCreate(): void
    // {
    //     parent::afterCreate();

    //     $notificationService = new NotificationService;
    //     $addedBy = auth()->user()->name; // معرفة من قام بالإضافة
    //     $shift = $this->record; // بيانات الوردية المضافة

    //     // في حال كانت علاقة المنطقة موجودة في نموذج الورديات
    //     $zoneName = isset($shift->zone) ? $shift->zone->name : 'غير متوفر';

    //     // إنشاء رسالة الإشعار مع التفاصيل الأساسية
    //     $message = "إضافة وردية جديدة\n\n";
    //     $message .= "تمت الإضافة بواسطة: {$addedBy}\n\n";
    //     $message .= "اسم الوردية: {$shift->name}\n";
    //     $message .= "المنطقة: {$zoneName}\n";
    //     $message .= "النوع: {$shift->type}\n";
    //     $message .= "تاريخ البدء: {$shift->start_date}\n";
    //     $message .= "عدد الموظفين: {$shift->emp_no}\n";

    //     $notificationService->sendNotification(
    //         ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
    //         'إضافة وردية جديدة', // عنوان الإشعار
    //         $message,
    //         [
    //             $notificationService->createAction('عرض الوردية', "/admin/shifts/{$shift->id}", ''),
    //             $notificationService->createAction('قائمة الورديات', '/admin/shifts', ''),
    //         ]
    //     );
    // }
}
