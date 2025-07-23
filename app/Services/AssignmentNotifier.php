<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Shift;
use App\Models\Project;
use App\Models\Employee;
use App\Models\EmployeeProjectRecord;
use App\Services\NotificationService;

class AssignmentNotifier
{
    public static function dispatchJobs(array $jobs): void
    {
        foreach ($jobs as $job) {
            $record = $job['record'];
            $type = $job['type'];

            match ($type) {
                'assign' => self::handleAssignment($record),
                'transfer_location' => self::handleTransferLocation($record),
                'transfer_employee' => self::handleTransferEmployee($record),
                'end' => self::handleEndAssignment($record),
                default => null,
            };
        }
    }

    protected static function handleAssignment(EmployeeProjectRecord $record): void
    {
        $notificationService = new NotificationService;
        $otpService = new OtpService;

        $employee = Employee::find($record->employee_id);
        $zone = Zone::find($record->zone_id);
        $project = Project::find($record->project_id);
        $shift = Shift::find($record->shift_id);

        $assignedBy = auth()->user()?->name ?? 'النظام';

        if (! $employee || ! $zone || ! $project || ! $shift) {
            return;
        }

        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'],
            '📌 إسناد موظف إلى موقع جديد',
            "👤 *اسم الموظف:* {$employee->name()}\n" .
                "📌 *الموقع:* {$zone->name} - {$project->name}\n" .
                "🕒 *الوردية:* {$shift->name}\n" .
                "📅 *تاريخ البدء:* {$record->start_date}\n" .
                '📅 *تاريخ الانتهاء:* ' . ($record->end_date ?? 'غير محدد') . "\n\n" .
                "🆔 *رقم الهوية:* {$employee->national_id}\n" .
                "📞 *الجوال:* {$employee->mobile_number}\n" .
                "📢 *تم الإسناد بواسطة:* {$assignedBy}",
            [
                $notificationService->createAction('عرض الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-map'),
            ]
        );

        try {
            $mobileNumber = preg_replace('/^966/', '', $employee->mobile_number);
            $cleanNumber = preg_replace('/[^0-9]/', '', $employee->mobile_number);

            $message = "مرحباً {$employee->name()},\n\n";
            $message .= "تم إسنادك إلى موقع جديد في النظام. تفاصيل الحساب:\n";
            $message .= "📌 *اسم المستخدم:* {$mobileNumber}\n";
            $message .= "🔑 *كلمة المرور:* {$employee->password}\n";
            $message .= "📍 *الموقع:* {$zone->name}\n";
            $message .= "🕒 *الوردية:* {$shift->name}\n";
            $message .= "📅 *تاريخ البدء:* {$record->start_date}\n\n";

            // روابط التطبيق
            $message .= "📥 *روابط تحميل التطبيق:*\n";
            $message .= "▶️ Android: https://play.google.com/store/apps/details?id=com.intshar.artalapp\n";
            $message .= "🍏 iOS: https://apps.apple.com/us/app/artal/id6740813953\n\n";

            // رابط جروب واتساب إن وُجد
            if ($project->has_whatsapp_group && $project->whatsapp_group_id) {
                try {
                    $whatsappService = new \App\Services\WhatsApp\WhatsAppGroupService();
                    $whatsappService->addParticipants($project->whatsapp_group_id, [$cleanNumber]);

                    $inviteLink = $whatsappService->getInviteLink($project->whatsapp_group_id);
                    if ($inviteLink) {
                        $message .= "📣 *رابط مجموعة المشروع:*\n{$inviteLink}\n\n";
                    }
                } catch (\Throwable $ex) {
                    \Log::warning('فشل إضافة الموظف للجروب أو جلب الرابط', [
                        'employee_id' => $employee->id,
                        'project_id' => $project->id,
                        'exception' => $ex->getMessage(),
                    ]);
                }
            }

            $message .= "شكراً لجهودك.";

            $otpService->sendOtp($employee->mobile_number, $message);
            $otpService->sendOtp('120363385699307538@g.us', $message);
        } catch (\Exception $e) {
            \Log::error('Failed to send OTP', [
                'employee_id' => $employee->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    protected static function handleTransferLocation(EmployeeProjectRecord $record): void
    {
        // نفس محتوى handleAssignment مع تغيير العنوان فقط
        $record->load(['employee', 'zone', 'project', 'shift']);
        if (! $record->employee || ! $record->zone || ! $record->project || ! $record->shift) {
            return;
        }

        $assignedBy = auth()->user()?->name ?? 'النظام';
        $notificationService = new NotificationService;

        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'],
            '📌 نقل موظف إلى موقع جديد',
            "👤 *اسم الموظف:* {$record->employee->name()}\n" .
                "📌 *الموقع الجديد:* {$record->zone->name} - {$record->project->name}\n" .
                "🕒 *الوردية:* {$record->shift->name}\n" .
                "📅 *تاريخ البدء:* {$record->start_date}\n" .
                '📅 *تاريخ الانتهاء:* ' . ($record->end_date ?? 'غير محدد') . "\n\n" .
                "🆔 *رقم الهوية:* {$record->employee->national_id}\n" .
                "📞 *الجوال:* {$record->employee->mobile_number}\n" .
                "📢 *تم النقل بواسطة:* {$assignedBy}",
            [
                $notificationService->createAction('عرض الموظف', "/admin/employees/{$record->employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$record->zone->id}", 'heroicon-s-map'),
            ]
        );
    }

    protected static function handleTransferEmployee(EmployeeProjectRecord $record): void
    {
        // نفس `handleTransferLocation` مع تغيير الترويسة فقط
        $record->load(['employee', 'zone', 'project', 'shift']);
        if (! $record->employee || ! $record->zone || ! $record->project || ! $record->shift) {
            return;
        }

        $assignedBy = auth()->user()?->name ?? 'النظام';

        $notificationService = new NotificationService;

        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'],
            '📌 نقل موظف إلى موقع جديد (تغيير موظف)',
            "👤 *اسم الموظف الجديد:* {$record->employee->name()}\n" .
                "📌 *الموقع:* {$record->zone->name} - {$record->project->name}\n" .
                "🕒 *الوردية:* {$record->shift->name}\n" .
                "📅 *تاريخ البدء:* {$record->start_date}\n" .
                '📅 *تاريخ الانتهاء:* ' . ($record->end_date ?? 'غير محدد') . "\n\n" .
                "🆔 *رقم الهوية:* {$record->employee->national_id}\n" .
                "📞 *الجوال:* {$record->employee->mobile_number}\n" .
                "📢 *تم النقل بواسطة:* {$assignedBy}",
            [
                $notificationService->createAction('عرض الموظف', "/admin/employees/{$record->employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$record->zone->id}", 'heroicon-s-map'),
            ]
        );
    }

    protected static function handleEndAssignment(EmployeeProjectRecord $record): void
    {
        $record->load(['employee', 'zone', 'project', 'shift']);
        if (! $record->employee || ! $record->zone || ! $record->project || ! $record->shift) {
            return;
        }
        $notificationService = new NotificationService;

        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'],
            '🚫 إنهاء إسناد موظف',
            "👤 *اسم الموظف:* {$record->employee->name()}\n" .
                "📌 *الموقع:* {$record->zone->name} - {$record->project->name}\n" .
                "🕒 *الوردية:* {$record->shift->name}\n" .
                '📅 *تاريخ الإنهاء:* ' . now()->toDateString() . "\n\n" .
                '📢 *تم الإنهاء ضمن عملية التحديث الجماعي.*',
            [
                $notificationService->createAction('عرض الموظف', "/admin/employees/{$record->employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$record->zone->id}", 'heroicon-s-map'),
            ]
        );

        // ✅ إزالة الموظف من جروب المشروع إن وُجد
        if (
            $record->project->has_whatsapp_group &&
            $record->project->whatsapp_group_id &&
            $record->employee->mobile_number
        ) {
            try {
                $whatsappService = new \App\Services\WhatsApp\WhatsAppGroupService();
                $cleanNumber = preg_replace('/[^0-9]/', '', $record->employee->mobile_number);

                $whatsappService->removeParticipant(
                    $record->project->whatsapp_group_id,
                    $cleanNumber
                );
            } catch (\Throwable $e) {
                \Log::warning('فشل إزالة الموظف من الجروب', [
                    'employee_id' => $record->employee->id,
                    'project_id' => $record->project->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
