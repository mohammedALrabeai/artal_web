<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Models\Role;
use App\Models\Leave;
use Filament\Actions;
use App\Models\Policy;
use App\Models\Employee;
use App\Models\ApprovalFlow;
use App\Services\NotificationService;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\RequestResource;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['submitted_by'] = auth()->id();
        // التحقق من وجود سياسة مرتبطة بنوع الطلب
        $policy = Policy::where('policy_type', $data['type'])->first();
        if (!$policy) {
            throw new \Exception(__('No policy defined for this request type.'));
        }

        // تحويل الشروط إلى مصفوفة إذا كانت نصًا
        $conditions = is_array($policy->conditions) ? $policy->conditions : json_decode($policy->conditions, true);
        if (!$conditions) {
            throw new \Exception(__('Policy conditions are invalid.'));
        }

        // التحقق من سلسلة الموافقات المرتبطة بنوع الطلب
        $approvalFlow = ApprovalFlow::where('request_type', $data['type'])
            ->orderBy('approval_level', 'asc')
            ->first();
        if (!$approvalFlow) {
            throw new \Exception(__('No approval flow defined for this request type.'));
        }

        // تخزين الدور الأول في `current_approver_role`
        $role = Role::where('name', $approvalFlow->approver_role)->first();
        if (!$role) {
            throw new \Exception(__('Role not found for the approver in the approval flow.'));
        }
        $data['current_approver_role'] = $role->name;
        $data['status'] = 'pending'; // الحالة المبدئية للطلب

        // التحقق من نوع الطلب وتطبيق القيود
        switch ($data['type']) {
            case 'leave': // طلب إجازة
                $employee = Employee::find($data['employee_id']);
                if (!$employee) {
                    throw new \Exception(__('Employee not found.'));
                }

                // الحصول على رصيد الإجازات السنوية من جدول leave_balances
                $leaveBalance = $employee->leaveBalances()->where('leave_type', 'annual')->first();
                // احتساب المدة بناءً على تاريخ البداية والنهاية
                $startDate = \Carbon\Carbon::parse($data['start_date']);
                $endDate = \Carbon\Carbon::parse($data['end_date']);
                $data['duration'] = $startDate->diffInDays($endDate) + 1; // +1 لإضافة اليوم الأول

                if (!$leaveBalance) {
                    // throw new \Exception(__('No leave balance record found for this employee.'));
                } else {
                    if ($data['leave_type'] == 'annual') {
                        // التحقق من أن رصيد الإجازات يكفي
                        if ($leaveBalance->calculateAnnualLeaveBalance() < $data['duration']) {
                            throw new \Exception(__('Insufficient leave balance.' . $leaveBalance->calculateAnnualLeaveBalance() . ' ' . $data['duration']));
                        }
                        $leaveBalance->update([
                            'balance' => $leaveBalance->balance - $data['duration'],
                            'used_balance' => $leaveBalance->used_balance + $data['duration'],
                            'last_updated' => now(),
                        ]);
                    }
                }



                // التحقق من الحد الأقصى لمدة الإجازة
                if (isset($conditions['max_duration']) && $data['duration'] > $conditions['max_duration']) {
                    throw new \Exception(__('Requested duration exceeds the maximum allowed.'));
                }


                $leave = Leave::create([
                    'employee_id' => $data['employee_id'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'type' => $data['leave_type'],
                    'reason' => $data['reason'],
                    'aproved' => false,
                ]);
                $data['leave_id'] = $leave->id;

                // تحديث الرصيد في جدول leave_balances عند إنشاء الطلب
                $leaveBalance->update([
                    'balance' => $leaveBalance->balance - $data['duration'],
                    'used_balance' => $leaveBalance->used_balance + $data['duration'],
                    'last_updated' => now(),
                ]);

                $notificationService = new NotificationService;
                $notificationService->sendNotification(
                    ['hr', 'manager'], // الأدوار المستهدفة
                    'طلب اجازة', // عنوان الإشعار
                    'يرجى مراجعة طلب الاجازة', // نص الإشعار
                    [
                        // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}", 'heroicon-s-eye'),
                        $notificationService->createAction('عرض قائمة الطلبات', '/admin/requests', 'heroicon-s-eye'),
                    ]
                );


                break;


            case 'loan': // طلب سلفة
                if (isset($conditions['max_amount']) && $data['amount'] > $conditions['max_amount']) {
                    throw new \Exception(__('Requested amount exceeds the maximum allowed.'));
                }

                $employee = Employee::find($data['employee_id']);
                if (!$employee) {
                    throw new \Exception(__('Employee not found.'));
                }

                $notificationService = new NotificationService;
                $notificationService->sendNotification(
                    ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
                    'طلب قرض جديد ', // عنوان الإشعار
                    $data['amount'].'  | '. $employee->first_name.' '. $employee->family_name.' | '.auth()->user()->name, // نص الإشعار
                    [
                        // $notificationService->createAction('View Bank', "/admin/banks/{$this->record->id}", 'heroicon-s-eye'),
                        $notificationService->createAction('عرض قائمة الطلبات', '/admin/requests', 'heroicon-s-eye'),
                    ]
                );
                break;

            case 'compensation': // طلب تعويض
                if (!isset($data['additional_data']['documentation'])) {
                    throw new \Exception(__('Documentation is required for compensation requests.'));
                }
                break;

            case 'transfer': // طلب نقل
                if (!isset($data['target_location'])) {
                    throw new \Exception(__('Target location is required for transfer requests.'));
                }
                break;

            case 'overtime': // طلب ساعات إضافية
                $employee = Employee::find($data['employee_id']);
                $overtimeBalance = $employee->leaveBalances()->where('leave_type', 'overtime')->first();

                if (!$overtimeBalance) {
                    throw new \Exception(__('No overtime balance record found for this employee.'));
                }

                if (isset($conditions['max_hours']) && $data['duration'] > $conditions['max_hours']) {
                    throw new \Exception(__('Overtime hours exceed the maximum allowed.'));
                }

                // تحديث الرصيد عند الموافقة على الساعات الإضافية
                $overtimeBalance->update([
                    'balance' => $overtimeBalance->balance - $data['duration'],
                    'used_balance' => $overtimeBalance->used_balance + $data['duration'],
                    'last_updated' => now(),
                ]);
                break;

            default:
                throw new \Exception(__('Invalid request type.'));
        }

        return $data;
    }
}
