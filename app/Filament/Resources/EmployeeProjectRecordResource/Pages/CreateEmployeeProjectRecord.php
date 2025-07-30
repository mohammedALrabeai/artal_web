<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use App\Models\Employee;
use App\Models\EmployeeProjectRecord;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Zone;
use App\Services\NotificationService;
use App\Services\OtpService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeProjectRecord extends CreateRecord
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * ✅ التحقق قبل إنشاء السجل
     */
    protected function beforeCreate(): void
    {
        $shift = Shift::find($this->data['shift_id']); // جلب الوردية المحددة
        $currentAssigned = EmployeeProjectRecord::where('shift_id', $this->data['shift_id'])
            ->where('status', '1') // ✅ فقط الموظفين النشطين
            ->count();

        if ($shift && $currentAssigned >= $shift->emp_no) {
            Notification::make()
                ->title('⚠️ العدد مكتمل')
                ->danger()
                ->body("❌ لا يمكن إسناد الموظف! العدد المطلوب للوردية ({$shift->name}) مكتمل ({$shift->emp_no}).")
                ->send();

            $this->halt(); // ⛔ منع الإسناد
        }
        $data = $this->data;

         if (! $data['shift_slot_id']) {
        Notification::make()
            ->title('❌ لا يمكن الحفظ بدون شاغر')
            ->danger()
            ->body('يرجى تحديد الشاغر قبل إنشاء الإسناد.')
            ->send();

        $this->halt();
    }

    $conflict = EmployeeProjectRecord::where('shift_id', $data['shift_id'])
        ->where('shift_slot_id', $data['shift_slot_id'])
        ->where('status', true)
        ->whereNull('end_date')
        ->exists();

    if ($conflict) {
        Notification::make()
            ->title('❌ الشاغر مستخدم بالفعل')
            ->danger()
            ->body('تم حجز هذا الشاغر مسبقًا لموظف آخر.')
            ->send();

        $this->halt();
    }

        $zone = Zone::find($this->data['zone_id']);
        $project = Project::find($this->data['project_id']);

        if ($shift && $zone && $project && $shift->zone_id !== $zone->id) {
            Notification::make()
                ->title('⚠️ وردية لا تتبع الموقع المحدد')
                ->danger()
                ->body("❌ الوردية المختارة ({$shift->name}) لا تتبع الموقع المحدد ({$zone->name}). يرجى اختيار وردية تابعة للموقع.")
                ->send();

            $this->halt(); // ⛔ منع الإسناد
        }

        if ($zone && $project && $zone->project_id !== $project->id) {
            Notification::make()
                ->title('⚠️ الموقع لا يتبع المشروع المحدد')
                ->danger()
                ->body("❌ الموقع المحدد ({$zone->name}) لا يتبع المشروع المحدد ({$project->name}).")
                ->send();

            $this->halt(); // ⛔ منع الإسناد
        }
    }

    protected function afterCreate(): void
    {
        $notificationService = new NotificationService;
        $employee = Employee::find($this->record->employee_id);
        $zone = Zone::find($this->record->zone_id);

        $assignedBy = auth()->user()->name; // معرفة من قام بالإسناد

        $project = Project::find($this->record->project_id);
        $shift = Shift::find($this->record->shift_id);
        // ✅ إذا كان الموظف غير نشط نقوم بتفعيله
        if ($employee && $employee->status != 1) {
            $employee->update(['status' => 1]);
        }
        if ($employee && $zone) {

            // ✅ **إرسال إشعار إلى المسؤولين عند إسناد الموظف**
            $notificationService->sendNotification(
                ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
                '📌 إسناد موظف إلى موقع جديد', // عنوان الإشعار
                "👤 *تم إسناد موظف جديد إلى موقع العمل!*\n\n" .
                    "👷 *اسم الموظف:* {$employee->name()}\n" .
                    "📌 *الموقع:* {$zone->name} - {$project->name}\n" .
                    "🕒 *الوردية:* {$shift->name}\n" .
                    "📅 *تاريخ البدء:* {$this->record->start_date}\n" .
                    '📅 *تاريخ الانتهاء:* ' . ($this->record->end_date ?? 'غير محدد') . "\n\n" .
                    "🆔 *رقم الهوية:* {$employee->national_id}\n" .
                    "📞 *رقم الجوال:* {$employee->mobile_number}\n" .
                    '📧 *البريد الإلكتروني:* ' . (! empty($employee->email) ? $employee->email : 'غير متوفر') . "\n\n" .
                    "📢 *تم الإسناد بواسطة:* {$assignedBy}\n",
                [
                    $notificationService->createAction(' عرض تفاصيل الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                    $notificationService->createAction(' عرض الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-map'),
                    // $notificationService->createAction('📋 قائمة الموظفين', '/admin/employees', 'heroicon-s-users'),
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

                // 📱 روابط تحميل التطبيق لأنظمة التشغيل المختلفة
                $message .= "📥 *لتحميل التطبيق:* \n";
                $message .= "▶️ *Android:* [Google Play](https://play.google.com/store/apps/details?id=com.intshar.artalapp)\n";
                $message .= "🍏 *iOS:* [App Store](https://apps.apple.com/us/app/artal/id6740813953)\n\n";

                if ($project && $project->has_whatsapp_group && $project->whatsapp_group_id && $employee->mobile_number) {
                    // إضافة إلى الجروب (بدون تعطيل لو فشل)
                    try {
                        $whatsappService = new \App\Services\WhatsApp\WhatsAppGroupService();
                        $cleanNumber = preg_replace('/[^0-9]/', '', $employee->mobile_number);

                        $whatsappService->addParticipants($project->whatsapp_group_id, [$cleanNumber]);

                        $inviteLink = $whatsappService->getInviteLink($project->whatsapp_group_id);
                        if ($inviteLink) {
                            $message .= "📣 *رابط مجموعة المشروع:*\n{$inviteLink}\n\n";
                        }
                    } catch (\Exception $ex) {
                        \Log::warning('فشل إضافة الموظف للجروب أو جلب الرابط', [
                            'employee_id' => $employee->id,
                            'project_id' => $project->id,
                            'exception' => $ex->getMessage(),
                        ]);
                    }
                }
                $message .= 'شكراً.';

                // 📲 إرسال الرسالة إلى رقم الجوال
                $otpService->sendOtp($employee->mobile_number, $message);
                // $otpService->sendOtp('120363385699307538@g.us', $message);
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
