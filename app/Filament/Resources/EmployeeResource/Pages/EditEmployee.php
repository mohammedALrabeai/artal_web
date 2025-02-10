<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
        $editedBy = auth()->user()->name; // معرفة من قام بالتعديل
        $employee = $this->record; // جلب بيانات الموظف بعد التعديل

        // ✅ جلب البيانات التي تم تغييرها
        $changes = $employee->getChanges();
        $original = $employee->getOriginal();

        // ✅ تجهيز قائمة التعديلات
        $changeDetails = '';
        foreach ($changes as $field => $newValue) {
            if (isset($original[$field]) && $original[$field] !== $newValue) {
                $changeDetails .= '🔹 **'.ucfirst(str_replace('_', ' ', $field)).":** `{$original[$field]}` → `{$newValue}`\n";
            }
        }

        // ✅ تجهيز نص الإشعار
        $message = "✏️ *تم تعديل بيانات الموظف بنجاح!*\n\n";
        $message .= "👤 *الموظف:* {$employee->name()}\n";
        $message .= "📌 *تم التعديل بواسطة:* {$editedBy}\n\n";
        $message .= "📝 *تفاصيل التعديل:*\n";
        $message .= ! empty($changeDetails) ? $changeDetails : "⚠️ لم يتم الكشف عن تغييرات كبيرة.\n";

        // ✅ إرسال الإشعار إلى المديرين
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            '✏️ تعديل بيانات الموظف', // عنوان الإشعار
            $message,
            [
                $notificationService->createAction('👁️ عرض بيانات الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('📋 قائمة الموظفين', '/admin/employees', 'heroicon-s-users'),
            ]
        );
    }
}
