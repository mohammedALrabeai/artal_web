<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Notifications\RequestStatusNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'submitted_by',
        'employee_id',
        'current_approver_role',
        'status',
        'description',
        'description',
        'duration',
        'amount',
        'additional_data',
        'required_documents',
        'target_location',
        'leave_id',
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];
    
    // علاقة مع المستخدم الذي قدّم الطلب
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // علاقة مع الموظف المرتبط بالطلب
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function leave()
    {
        return $this->belongsTo(Leave::class, 'leave_id');
    }
    public function exclusion()
    {
        return $this->hasOne(\App\Models\Exclusion::class);
    }

  
    public function approvals()
{
    return $this->hasMany(RequestApproval::class)
        ->orderBy('approved_at', 'asc'); // ترتيب الموافقات حسب الوقت
}


    // المستخدم الذي يوافق حاليًا
    public function currentApprover()
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    public function approvalFlows()
{
    return $this->hasMany(ApprovalFlow::class, 'request_type', 'type');
}
 

public function updateRequestStatus()
{
    $approvals = $this->approvals; // جلب جميع الموافقات المرتبطة بالطلب

    if ($approvals->every(fn($approval) => $approval->status === 'approved')) {
        $this->status = 'approved'; // إذا تمت الموافقة على جميع المستويات
    } elseif ($approvals->contains(fn($approval) => $approval->status === 'rejected')) {
        $this->status = 'rejected'; // إذا تم رفض أحد المستويات
    } else {
        $this->status = 'pending'; // إذا كانت الموافقة قيد الانتظار
    }

    $this->save();

    // إرسال إشعار
    $this->employee->notify(
        new RequestStatusNotification($this, $this->status, auth()->user(), null)
    );
}

public function approveRequest($approver, $comments = null)
{
    // التحقق من أن حالة الطلب تسمح بالموافقة
    if ($this->status !== 'pending') {
        throw new \Exception(__('This request cannot be approved as it is already :status.', ['status' => $this->status]));
    }
    \Log::info('Approver Roles:', ['user_roles' => $approver->getRoleNames()]);
    \Log::info('Request Current Approver Role:', ['current_approver_role' => $this->current_approver_role]);

   // 🔹 التأكد من أن المستخدم لديه أحد الأدوار المطلوبة
   $approverRoles = $approver->getRoleNames()->toArray(); // الحصول على جميع الأدوار كـ array

   if (!in_array(strtolower($this->current_approver_role), array_map('strtolower', $approverRoles))) {
    throw new \Exception(__('You are not authorized to approve this request. Your roles: :roles, Required role: :required_role', [
        'roles' => implode(', ', $approverRoles),
        'required_role' => $this->current_approver_role,
    ]));
}

    // التحقق مما إذا كان المسؤول قد وافق مسبقًا
    $existingApproval = $this->approvals()
        ->where('approver_id', $approver->id)
        ->where('approver_role', $approver->role->name)
        ->where('status', 'approved')
        ->first();

    if ($existingApproval) {
        throw new \Exception(__('You have already approved this request.'));
    }

    // التحقق من سلسلة الموافقات
    $approvalFlow = $this->approvalFlows
        ->where('approver_role', $this->current_approver_role)
        ->first();

    if (!$approvalFlow) {
        throw new \Exception(__('Approval flow is not properly configured for this request type.'));
    }

    // التحقق من الشروط الإضافية (مثال: رصيد الإجازات)
 
    if ($this->type === 'leave' && isset($approvalFlow->conditions['min_balance']) && $approvalFlow->conditions['min_balance']) {
        $employee = $this->employee;
        if ($employee->leave_balance < $approvalFlow->conditions['min_balance']) {
            throw new \Exception(__('Insufficient leave balance for approval.'));
        }
    }

    // التحقق من البيانات المطلوبة (مثال: التعليقات)
    if ($approvalFlow->conditions['requires_comments'] ?? false && empty($comments)) {
        throw new \Exception(__('Comments are required for this approval.'));
    }

    // جلب المستوى التالي من سلسلة الموافقات
    $currentApprovalLevel = $approvalFlow->approval_level;

    $nextApprovalFlow = $this->approvalFlows()
        ->where('approval_level', '>', $currentApprovalLevel)
        ->orderBy('approval_level', 'asc')
        ->first();
    

    if ($nextApprovalFlow) {
        // إذا كان هناك مستوى موافقة آخر
        $this->current_approver_role = $nextApprovalFlow->name;
    } else {
        // إذا انتهت جميع المستويات
        $this->current_approver_role = null;
        $this->status = 'approved';
           // تحديث حالة الإجازة إذا كانت الموافقة نهائية
           if ($this->type === 'leave' && $this->leave) {
            $this->leave->update([
                'approved' => true, // تحديث حالة الإجازة إلى "معتمدة"
            ]);
            // انشاء سجل الاجازة في جدول التحضير
            $this->makeLeaveAttendance();


        }
    }

    $this->save();

    // تسجيل الموافقة
    $this->approvals()->create([
        'approver_id' => $approver->id,
        'approver_role' => $approverRoleName, // تخزين اسم الدور
        'status' => 'approved',
        'approved_at' => now(),
        'notes' => $comments,
    ]);
    

    // إشعار الموظف إذا كانت الموافقة النهائية
    if ($this->status === 'approved') {
        $this->employee->notify(
            new RequestStatusNotification($this, 'approved', $approver, $comments)
        );
    }
}


public function rejectRequest($approver, $comments = null)
{

    $this->status = 'rejected';
    $this->current_approver_role = null; // لا مزيد من الموافقات بعد الرفض
    $this->save();

    // تسجيل الرفض
    $this->approvals()->create([
        'approver_id' => $approver->id,
        'approver_role' => $approver->role,
        'status' => 'rejected',
        'approved_at' => now(),
        'notes' =>  $comments,
    ]);
    if (empty($this->employee->mobile_number)) {
        \Log::warning('Employee does not have a mobile number.', [
            'employee_id' => $this->employee->id,
            'request_id' => $this->id,
        ]);
        return; // إنهاء العملية إذا لم يكن هناك رقم جوال
    }
    
    \Log::info('About to send notification.', [
        'employee_id' => $this->employee->id,
        'status' => $this->status,
    ]);


    // التحقق من نوع الطلب (إجازة سنوية)
    if ($this->type === 'leave' &&false) {    // يلزم تفاصيل عن نوع الاجازة
        // الحصول على رصيد الإجازة السنوية
        $leaveBalance = $this->employee->leaveBalances()->where('leave_type', 'annual')->first();

        if ($leaveBalance) {
            // تحديث الرصيد عند الرفض
            $leaveBalance->update([
                'balance' => $leaveBalance->balance + $this->duration, // إعادة الأيام المستخدمة
                'used_balance' => $leaveBalance->used_balance - $this->duration,
                'last_updated' => now(),
            ]);

            \Log::info('Leave balance updated successfully upon rejection.', [
                'employee_id' => $this->employee->id,
                'request_id' => $this->id,
                'returned_days' => $this->duration,
            ]);
        } else {
            \Log::error('Leave balance record not found for employee.', [
                'employee_id' => $this->employee->id,
                'request_id' => $this->id,
            ]);
        }
    }


 
    // إشعار الموظف بالرفض
    $this->employee()->notify(
        new RequestStatusNotification($this, 'rejected', $approver, $comments)
    );
    \Log::info('Notification sent successfully.');


}

public function makeLeaveAttendance()
{
    // التحقق من وجود الإجازة
    if (!$this->leave) {
        \Log::error('Leave record not found for this request.', [
            'request_id' => $this->id,
        ]);
        return;
    }

    // جلب تواريخ البداية والنهاية
    $startDate = \Carbon\Carbon::parse($this->leave->start_date);
    $endDate = \Carbon\Carbon::parse($this->leave->end_date);

    // التحقق من الموظف
    if (!$this->employee) {
        \Log::error('Employee not found for this request.', [
            'request_id' => $this->id,
        ]);
        return;
    }

    // جلب سجل المشروع الحالي للموظف
    $projectRecord = $this->employee->currentProjectRecord;

    if (!$projectRecord || !$projectRecord->zone || !$projectRecord->shift) {
        \Log::error('Project record, zone, or shift not found for employee.', [
            'employee_id' => $this->employee->id,
            'request_id' => $this->id,
        ]);
        return;
    }

    // جلب المنطقة والوردية من سجل المشروع
    $zoneId = $projectRecord->zone_id;
    $shiftId = $projectRecord->shift_id;

    // التحقق من تواريخ البداية والنهاية
    if (!$startDate || !$endDate) {
        \Log::error('Invalid start or end date for leave.', [
            'leave_id' => $this->leave->id,
        ]);
        return;
    }

    // إنشاء السجلات في جدول التحضيرات لكل يوم ضمن فترة الإجازة
    $currentDate = $startDate->copy();
    while ($currentDate->lte($endDate)) {
        try {
            // التحقق مما إذا كان اليوم يوم عمل
            $isWorkingDay = $projectRecord->isWorkingDay();
            if (!$isWorkingDay) {
                \Log::info('Skipping non-working day.', [
                    'employee_id' => $this->employee->id,
                    'date' => $currentDate->toDateString(),
                ]);
                $currentDate->addDay();
                continue;
            }

            // إنشاء سجل الحضور
            \App\Models\Attendance::firstOrCreate(
                ['employee_id' => $this->employee_id, 'date' => $currentDate->toDateString()],
                [
                    'zone_id' => $zoneId,
                    'shift_id' => $shiftId,
                    'ismorning'=>true,
                    'status' => 'leave', // حالة الحضور "إجازة"
                    'notes' => 'Leave: '.$this->leave->id.' - request ID: '.$this->id.': ' . $this->leave->type.' - ' . $this->leave->reason.' - ' . $this->leave->start_date . ' - ' . $this->leave->end_date, // ملاحظات
                    // 'request_id' => $this->id, // ربط بالسجل الخاص بالطلب
                ]
            );
            \Log::info('Attendance record created for leave.', [
                'employee_id' => $this->employee_id,
                'date' => $currentDate->toDateString(),
                'status' => 'leave',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create attendance record.', [
                'employee_id' => $this->employee_id,
                'date' => $currentDate->toDateString(),
                'error' => $e->getMessage(),
            ]);
        }

        $currentDate->addDay(); // الانتقال إلى اليوم التالي
    }
}


public function attachments(): MorphMany
{
    return $this->morphMany(Attachment::class, 'model');
}


}
