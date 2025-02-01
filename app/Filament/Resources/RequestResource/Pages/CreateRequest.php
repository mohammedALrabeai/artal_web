<?php

namespace App\Filament\Resources\RequestResource\Pages;

// use App\Models\Role;
use Spatie\Permission\Models\Role;

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

    public $selectedEmployeeId;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['submitted_by'] = auth()->id();

        // if (!isset($data['attachments'])) {
        //     throw new \Exception(__('Attachments data is missing.'));
        // }
        // التحقق من وجود سياسة مرتبطة بنوع الطلب
       // استثناء أنواع الطلبات التي لا تحتاج إلى سياسة
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
  

        // $roleExists = Role::where('name', $approvalFlow->approver_role)->exists();
        // if (!$roleExists) {
        //     throw new \Exception(__('Role not found for the approver in the approval flow.'));
        // }
        $data['current_approver_role'] = $approvalFlow->approver_role; // الآن يتم حفظ اسم الدور فقط
        
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


                case 'exclusion': // طلب استبعاد
                
                    $employee = Employee::find($data['employee_id']);
                    if (!$employee) {
                        throw new \Exception(__('Employee not found.'));
                    }
                
                    // التحقق من سياسة الاستبعاد إذا كانت موجودة
                    if (!isset($conditions['allowed_exclusions'])) {
                        throw new \Exception(__('Allowed exclusions are not defined in the policy.'));
                    }
                    
                    if (!in_array(strtolower($data['exclusion_type']), array_map('strtolower', $conditions['allowed_exclusions']))) {
                        throw new \Exception(__('The selected exclusion type (:type) is not allowed.', ['type' => $data['exclusion_type']]));
                    }
                
                    try{
                    // إنشاء سجل استبعاد
                    $exclusion = \App\Models\Exclusion::create([
                        'employee_id' => $data['employee_id'],
                        'type' => $data['exclusion_type'],
                        'exclusion_date' => $data['exclusion_date'],
                        'reason' => $data['exclusion_reason'],
                        'attachment' => $data['exclusion_attachment'] ?? null,
                        'notes' => $data['exclusion_notes'] ?? null,
                    ]);
                
                    $data['exclusion_id'] = $exclusion->id; // ربط الطلب بسجل الاستبعاد
                
                    // إرسال إشعار للأدوار المستهدفة
                    $notificationService = new NotificationService;
                    $notificationService->sendNotification(
                        ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
                        'طلب استبعاد', // عنوان الإشعار
                        'يرجى مراجعة طلب الاستبعاد للموظف ' . $employee->first_name . ' ' . $employee->family_name, // نص الإشعار
                        [
                            $notificationService->createAction('عرض قائمة الطلبات', '/admin/requests', 'heroicon-s-eye'),
                        ]
                    );

                } catch (\Exception $e) {
                    dd($e->getMessage());
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


        if (!isset($data['employee_id'])) {
            throw new \Exception(__('Employee ID is required.'));
        }
    
        if (isset($data['attachments'])) {
            foreach ($data['attachments'] as &$attachment) {
                if (!isset($attachment['employee_id'])) {
                    $attachment['employee_id'] = $data['employee_id']; // تأكد من تمرير employee_id
                }
            }
        }

        return $data;
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     // dd($data);
    //     if (isset($data['attachments'])) {
    //         foreach ($data['attachments'] as $attachment) {
    //             $this->record->attachments()->create([
    //                 'title' => $attachment['title'],
    //                 'type' => $attachment['type'],
    //                 'content' => $attachment['content'] ?? null,
    //                 'file_url' => $attachment['file_url'] ?? null,
    //                 'image_url' => $attachment['image_url'] ?? null,
    //                 'video_url' => $attachment['video_url'] ?? null,
    //                 'expiry_date' => $attachment['expiry_date'] ?? null,
    //                 'notes' => $attachment['notes'] ?? null,
    //                 'employee_id' => $data['employee_id'], // استخدام employee_id المرتبط بالطلب
    //                 'added_by' => auth()->id(),
    //             ]);
    //         }
    //     }
    
    //     return $data;
    // }

//     protected function mutateFormDataBeforeSave(array $data): array
// {
//     if (isset($data['attachments'])) {
//         foreach ($data['attachments'] as &$attachment) {
//             if (!isset($attachment['employee_id'])) {
//                 $attachment['employee_id'] = $data['related_employee_id']; // تمرير employee_id إذا لم يكن موجودًا
//             }
//         }
//     }

//     return $data;
// }

protected function afterSave(): void
{
   
    
        // // Handle media separately if needed
        // if (!empty($data['media'])) {
        //     $employee = $this->getOwnerRecord();
        //     $filePath =  $this->data['media'];
    
        //     // Ensure file exists on S3 before saving
        //     if (\Storage::disk('s3')->exists($filePath)) {
        //         $media = $employee
        //             ->addMediaFromDisk($filePath, 's3')
        //             ->toMediaCollection();
        //         $data['media'] = $media->id; // Store media ID or URL
        //     } else {
        //         throw new \Exception('The uploaded file does not exist on S3.');
        //     }
        // }
        // $this->record->attachments()->create($data);
    
      
  
    foreach ($this->record->attachments as $attachment) {
        if (!$attachment->employee_id) {
            $attachment->update(['employee_id' => $this->record->employee_id]);
        }
    }
}





    
}
