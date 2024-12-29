<?php

namespace App\Filament\Resources\RequestResource\Pages;

use Filament\Actions;
use App\Models\Policy;
use App\Models\Employee;
use App\Models\ApprovalFlow;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\RequestResource;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
    $data['current_approver_role'] = $approvalFlow->approver_role;
    $data['status'] = 'pending'; // الحالة المبدئية للطلب
    
        // التحقق من نوع الطلب وتطبيق القيود
        switch ($data['type']) {
            case 'leave': // طلب إجازة
                $employee = Employee::find($data['employee_id']);
                if (!$employee) {
                    throw new \Exception(__('Employee not found.'));
                }
    
                if ($employee->leave_balance < $data['duration']) {
                    throw new \Exception(__('Insufficient leave balance.'));
                }
    
                if (isset($conditions['max_duration']) && $data['duration'] > $conditions['max_duration']) {
                    throw new \Exception(__('Requested duration exceeds the maximum allowed.'));
                }
                break;
    
            case 'loan': // طلب سلفة
                if (isset($conditions['max_amount']) && $data['amount'] > $conditions['max_amount']) {
                    throw new \Exception(__('Requested amount exceeds the maximum allowed.'));
                }
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
                if (isset($conditions['max_hours']) && $data['duration'] > $conditions['max_hours']) {
                    throw new \Exception(__('Overtime hours exceed the maximum allowed.'));
                }
                break;
    
            default:
                throw new \Exception(__('Invalid request type.'));
        }
    
        return $data;
    }
    
    
    
    
    
}
