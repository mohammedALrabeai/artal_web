<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use App\Models\Employee;
use App\Models\Zone;
use App\Services\NotificationService;
use App\Services\OtpService;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeProjectRecord extends CreateRecord
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $notificationService = new NotificationService;
        $employee = Employee::find($this->record->employee_id);
        $zone = Zone::find($this->record->zone_id);

        if ($employee && $zone) {
            // 🛑 إرسال إشعار للمسؤولين عند إسناد الموظف إلى الموقع
            $notificationService->sendNotification(
                ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
                'إسناد موظف إلى موقع جديد', // عنوان الإشعار
                "تم إسناد الموظف {$employee->name()} إلى الموقع {$zone->name}", // نص الإشعار
                [
                    $notificationService->createAction('عرض تفاصيل الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                    $notificationService->createAction('عرض جميع المواقع', '/admin/zones', 'heroicon-s-map'),
                ]
            );

            // 🛑 إرسال رسالة إلى الموظف تحتوي على جميع بياناته
            try {
                $otpService = new OtpService;
                $mobileNumber = preg_replace('/^966/', '', $employee->mobile_number);

                // 📨 تحضير نص الرسالة
                $message = "مرحباً {$employee->name()},\n\n";
                $message .= "تم إسنادك إلى موقع جديد في النظام. تفاصيل حسابك:\n";
                $message .= "اسم المستخدم: {$mobileNumber}\n";
                $message .= "كلمة المرور: {$employee->password}\n";
                // $message .= "المسمى الوظيفي: {$employee->job_title}\n";
                $message .= "الموقع: {$zone->name}\n";
                // $message .= "رقم الهوية الوطنية: {$employee->national_id}\n";
                // $message .= "البريد الإلكتروني: {$employee->email}\n\n";
                $message .= "الرجاء تغيير كلمة المرور عند تسجيل الدخول لأول مرة.\n";
                $message .= "لتحميل التطبيق، يرجى النقر على الرابط التالي:\n";
                $message .= "🔗 https://play.google.com/store/apps/details?id=com.intshar.artalapp\n\n";
                $message .= 'شكراً.';

                // 📲 إرسال الرسالة إلى رقم الجوال
                $otpService->sendOtp($employee->mobile_number, $message);

            } catch (\Exception $e) {
                \Log::error('Error sending OTP message to assigned employee.', [
                    'exception' => $e,
                    'employee_id' => $employee->id,
                    'mobile_number' => $employee->mobile_number,
                ]);
            }
        }

    }
}
