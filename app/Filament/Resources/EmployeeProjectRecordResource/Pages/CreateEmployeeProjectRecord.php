<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Shift;
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

        $assignedBy = auth()->user()->name; // معرفة من قام بالإسناد

        $project = Project::find($this->record->project_id);
        $shift = Shift::find($this->record->shift_id);

        if ($employee && $zone) {

            // ✅ **إرسال إشعار إلى المسؤولين عند إسناد الموظف**
            $notificationService->sendNotification(
                ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
                '📌 إسناد موظف إلى موقع جديد', // عنوان الإشعار
                "👤 *تم إسناد موظف جديد إلى موقع العمل!*\n\n".
                "👷 *اسم الموظف:* {$employee->name()}\n".
                "📌 *الموقع:* {$zone->name} - {$project->name}\n".
                "🕒 *الوردية:* {$shift->name}\n".
                "📅 *تاريخ البدء:* {$this->record->start_date}\n".
                '📅 *تاريخ الانتهاء:* '.($this->record->end_date ?? 'غير محدد')."\n\n".
                "🆔 *رقم الهوية:* {$employee->national_id}\n".
                "📞 *رقم الجوال:* {$employee->mobile_number}\n".
                '📧 *البريد الإلكتروني:* '.(! empty($employee->email) ? $employee->email : 'غير متوفر')."\n\n".
                "📢 *تم الإسناد بواسطة:* {$assignedBy}\n",
                [
                    $notificationService->createAction('👁️ عرض تفاصيل الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                    $notificationService->createAction('🗺️ عرض الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-map'),
                    $notificationService->createAction('📋 قائمة الموظفين', '/admin/employees', 'heroicon-s-users'),
                ]
            );

            // 🛑 إرسال رسالة إلى الموظف تحتوي على جميع بياناته
            try {
                $otpService = new OtpService;
                $mobileNumber = preg_replace('/^966/', '', $employee->mobile_number);

                // 📨 تحضير نص الرسالة
                $message = "مرحباً {$employee->name()},\n\n";
                $message .= "تم إسنادك إلى موقع جديد في النظام. تفاصيل حسابك:\n";
                $message .= "📌 *اسم المستخدم:* {$mobileNumber}\n";
                $message .= "🔑 *كلمة المرور:* {$employee->password}\n";
                $message .= "📍 *الموقع:* {$zone->name}\n\n";
                $message .= "⚠️ *الرجاء تغيير كلمة المرور عند تسجيل الدخول لأول مرة.*\n\n";

                // 📱 روابط تحميل التطبيق لأنظمة التشغيل المختلفة
                $message .= "📥 *لتحميل التطبيق:* \n";
                $message .= "▶️ *Android:* [Google Play](https://play.google.com/store/apps/details?id=com.intshar.artalapp)\n";
                $message .= "🍏 *iOS:* [TestFlight](https://testflight.apple.com/join/Md5YzFE7)\n\n";

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
