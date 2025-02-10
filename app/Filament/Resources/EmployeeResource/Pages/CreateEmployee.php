<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Services\NotificationService;
use App\Services\OtpService;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ✅ **تصحيح رقم الجوال قبل الحفظ**
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['mobile_number'] = $this->formatSaudiMobileNumber($data['mobile_number']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $notificationService = new NotificationService;
        $addedBy = auth()->user()->name; // معرفة من قام بالإضافة
        $employee = $this->record; // جلب بيانات الموظف الجديد

        // ✅ إنشاء رسالة الإشعار بتنسيق محسن
        $message = "👤 *إضافة موظف جديد*\n\n";
        $message .= "📢 *تمت إضافة الموظف بواسطة:* {$addedBy}\n\n";
        $message .= "👨‍💼 *اسم الموظف:* {$employee->name()}\n";
        $message .= "🆔 *رقم الهوية:* {$employee->national_id}\n";
        $message .= "📞 *رقم الجوال:* {$employee->mobile_number}\n";

        if (! empty($employee->email)) {
            $message .= "📧 *البريد الإلكتروني:* {$employee->email}\n";
        }

        $message .= "🏢 *المسمى الوظيفي:* {$employee->job_title}\n";

        if (! empty($employee->birth_date)) {
            $message .= "📅 *تاريخ الميلاد:* {$employee->birth_date}\n";
        }

        $message .= '🏡 *مكان الميلاد:* '.(! empty($employee->birth_place) ? $employee->birth_place : 'غير متوفر')."\n\n";

        // ✅ إرسال الإشعار مع الروابط
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            '👤 إضافة موظف جديد', // عنوان الإشعار
            $message,
            [
                $notificationService->createAction('👁️ عرض بيانات الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('📋 قائمة الموظفين', '/admin/employees', 'heroicon-s-users'),
            ]
        );
        try {
            $otpService = new OtpService;
            $employee = $this->record; // جلب بيانات الموظف المرتبطة

            // طباعة بيانات الموظف في السجلات
            \Log::info('Employee Data:', [
                'id' => $employee->id,
                'name' => $employee->name(),
                'email' => $employee->email,
                'phone' => $employee->phone,
            ]);

            // $message ="";

            // $message = "مرحباً {$employee->name()},\n\n";
            // $message .= "تم تسجيلك في النظام بنجاح. بيانات الدخول الخاصة بك هي:\n";
            // $message .= "اسم المستخدم: {$employee->mobile_number}\n";
            // $message .= "كلمة المرور: {$employee->password}\n\n";
            // $message .= "الرجاء تغيير كلمة المرور عند تسجيل الدخول لأول مرة.\n";
            // $message .= "لتحميل التطبيق، يرجى النقر على الرابط التالي:\n";
            // $message .= "🔗 https://artalsys.com/api/download-apk/artal_app.apk\n\n";
            // $message .= "شكراً.";
            // $otpService->sendOtp($employee->mobile_number, $message);
        } catch (\Exception $e) {
            \Log::error('Error sending OTP message.', [
                'exception' => $e,
            ]);
        }

    }

    private function formatSaudiMobileNumber($number)
    {
        // تنظيف الرقم من أي مسافات أو رموز غير رقمية
        $cleanedNumber = preg_replace('/\D/', '', trim($number));

        // ✅ إذا كان الرقم يحتوي على رمز الدولة مسبقًا (+966 أو 00966)، قم بإزالته
        if (preg_match('/^(?:\+966|00966)/', $cleanedNumber)) {
            $cleanedNumber = preg_replace('/^(?:\+966|00966)/', '', $cleanedNumber);
        }

        // ✅ إذا كان الرقم يبدأ بـ "05"، احذف الصفر الأول
        if (preg_match('/^05\d{8}$/', $cleanedNumber)) {
            $cleanedNumber = substr($cleanedNumber, 1);
        }

        // ✅ **الشرط الجديد: إذا كان الرقم يبدأ بـ "5" مباشرة وكان بطول 9 أرقام، أضف مفتاح الدولة `966`**
        if (preg_match('/^5\d{8}$/', $cleanedNumber)) {
            return '966'.$cleanedNumber;
        }

        // ❌ إذا لم يكن الرقم بصيغة صحيحة، احتفظ بالرقم كما هو دون تغيير
        return $cleanedNumber;
    }
}
