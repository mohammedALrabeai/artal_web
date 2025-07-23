<?php

namespace App\Services\WhatsApp;

use App\Models\Employee;
use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Facades\Log;

class WhatsappGroupManager
{
    public static function addEmployee(EmployeeProjectRecord $record): void
    {
        $employee = $record->employee;
        $project = $record->project;

        if (! $employee || ! $project || ! $project->has_whatsapp_group || ! $employee->mobile_number) {
            return;
        }

        try {
            $cleanNumber = preg_replace('/[^0-9]/', '', $employee->mobile_number);
            $service = new WhatsAppGroupService();
            $service->addParticipants($project->whatsapp_group_id, [$cleanNumber]);

            $inviteLink = $service->getInviteLink($project->whatsapp_group_id);
            if ($inviteLink) {
                $msgService = new WhatsAppMessageService();
                $msgService->sendMessage($cleanNumber, "📌 تم إسنادك إلى مشروع: {$project->name}\n\nالانضمام إلى الجروب:\n{$inviteLink}");
            }
        } catch (\Throwable $e) {
            Log::warning('فشل في إضافة الموظف إلى جروب واتساب', [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public static function removeEmployee(EmployeeProjectRecord $record): void
    {
        $employee = $record->employee;
        $project = $record->project;

        if (! $employee || ! $project || ! $project->has_whatsapp_group || ! $employee->mobile_number) {
            return;
        }

        try {
            $cleanNumber = preg_replace('/[^0-9]/', '', $employee->mobile_number);
            $service = new WhatsAppGroupService();
            $service->removeParticipant($project->whatsapp_group_id, $cleanNumber);
        } catch (\Throwable $e) {
            Log::warning('فشل في إزالة الموظف من جروب واتساب', [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
