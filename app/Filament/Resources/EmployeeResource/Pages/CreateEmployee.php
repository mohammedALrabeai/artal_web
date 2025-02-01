<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Models\User;
use App\Services\OtpService;
use App\Services\NotificationService;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\EmployeeResource;
use App\Notifications\NewEmployeeNotification;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'اضافة موظف جديد', // عنوان الإشعار
            'تم اضافة موظف جديد بنجاح!', // نص الإشعار
            [
                $notificationService->createAction('عرض بيانات الموظف', "/admin/employees/{$this->record->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض قائمة الموظفين', '/admin/banks', 'heroicon-s-eye'),
            ]
        );
try {
    $otpService = new OtpService();
    $employee = $this->record; // جلب بيانات الموظف المرتبطة

    // طباعة بيانات الموظف في السجلات
    \Log::info('Employee Data:', [
        'id' => $employee->id,
        'name' => $employee->name(),
        'email' => $employee->email,
        'phone' => $employee->phone,
    ]);

    $message ="";

    $message = "مرحباً {$employee->name()},\n\n";
    $message .= "تم تسجيلك في النظام بنجاح. بيانات الدخول الخاصة بك هي:\n";
    $message .= "اسم المستخدم: {$employee->mobile_number}\n";
    $message .= "كلمة المرور: {$employee->password}\n\n";
    $message .= "الرجاء تغيير كلمة المرور عند تسجيل الدخول لأول مرة.\n";
    $message .= "لتحميل التطبيق، يرجى النقر على الرابط التالي:\n";
    $message .= "🔗 https://artalsys.com/api/download-apk/artal_app.apk\n\n";
    $message .= "شكراً.";
    $otpService->sendOtp($employee->mobile_number, $message);
} catch (\Exception $e) {
    \Log::error('Error sending OTP message.', [
        'exception' => $e,
    ]);
}


    }
}
