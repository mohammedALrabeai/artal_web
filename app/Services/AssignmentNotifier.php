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
            "👤 *اسم الموظف:* {$employee->name()}\n".
            "📌 *الموقع:* {$zone->name} - {$project->name}\n".
            "🕒 *الوردية:* {$shift->name}\n".
            "📅 *تاريخ البدء:* {$record->start_date}\n".
            '📅 *تاريخ الانتهاء:* '.($record->end_date ?? 'غير محدد')."\n\n".
            "🆔 *رقم الهوية:* {$employee->national_id}\n".
            "📞 *الجوال:* {$employee->mobile_number}\n".
            "📢 *تم الإسناد بواسطة:* {$assignedBy}",
            [
                $notificationService->createAction('عرض الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-map'),
            ]
        );

        try {
            $mobileNumber = preg_replace('/^966/', '', $employee->mobile_number);

            $message = "مرحباً {$employee->name()},\n\n";
            $message .= "تم إسنادك إلى موقع جديد:\n";
            $message .= "📍 *{$zone->name}*\n🕒 *{$shift->name}*\n";
            $message .= "📅 *تاريخ البدء:* {$record->start_date}\n\n";
            $message .= "📥 لتحميل التطبيق:\n";
            $message .= "▶️ Android: https://play.google.com/store/apps/details?id=com.intshar.artalapp\n";
            $message .= "🍏 iOS: https://apps.apple.com/us/app/artal/id6740813953\n\n";
            $message .= 'شكراً.';

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
            "👤 *اسم الموظف:* {$record->employee->name()}\n".
            "📌 *الموقع الجديد:* {$record->zone->name} - {$record->project->name}\n".
            "🕒 *الوردية:* {$record->shift->name}\n".
            "📅 *تاريخ البدء:* {$record->start_date}\n".
            '📅 *تاريخ الانتهاء:* '.($record->end_date ?? 'غير محدد')."\n\n".
            "🆔 *رقم الهوية:* {$record->employee->national_id}\n".
            "📞 *الجوال:* {$record->employee->mobile_number}\n".
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
            "👤 *اسم الموظف الجديد:* {$record->employee->name()}\n".
            "📌 *الموقع:* {$record->zone->name} - {$record->project->name}\n".
            "🕒 *الوردية:* {$record->shift->name}\n".
            "📅 *تاريخ البدء:* {$record->start_date}\n".
            '📅 *تاريخ الانتهاء:* '.($record->end_date ?? 'غير محدد')."\n\n".
            "🆔 *رقم الهوية:* {$record->employee->national_id}\n".
            "📞 *الجوال:* {$record->employee->mobile_number}\n".
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
            "👤 *اسم الموظف:* {$record->employee->name()}\n".
            "📌 *الموقع:* {$record->zone->name} - {$record->project->name}\n".
            "🕒 *الوردية:* {$record->shift->name}\n".
            '📅 *تاريخ الإنهاء:* '.now()->toDateString()."\n\n".
            '📢 *تم الإنهاء ضمن عملية التحديث الجماعي.*',
            [
                $notificationService->createAction('عرض الموظف', "/admin/employees/{$record->employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$record->zone->id}", 'heroicon-s-map'),
            ]
        );
    }
}
