<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Services\NotificationService;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\EmployeeResource;
use App\Notifications\NewEmployeeNotification;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'تعديل بيانات الموظف', // عنوان الإشعار
            'تم تعديل بيانات الموظف بنجاح!', // نص الإشعار
            [
                $notificationService->createAction('عرض بيانات الموظف', "/admin/employees/{$this->record->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة الموظفين', '/admin/banks', 'heroicon-s-eye'),
            ]
        );
       
    //     // إرسال إشعار بتعديل بيانات الموظف
    //      // جلب المستخدمين الذين لديهم الأدوار المطلوبة عبر العلاقة مع جدول الأدوار
    // $managers = User::whereHas('role', function ($query) {
    //     $query->whereIn('name', ['manager', 'general_manager', 'hr']); // الأدوار المطلوبة
    // })->get();

    // // إرسال الإشعارات
    // foreach ($managers as $manager) {
    //     $manager->notify(new NewEmployeeNotification($this->record));
    // }
    }
}
