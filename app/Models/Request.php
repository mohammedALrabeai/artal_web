<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Notifications\RequestStatusNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'submitted_by',
        'employee_id',
        // 'current_approver_id',
        'current_approver_role',
        'status',
        'description',
        'description',
        'duration',
        'amount',
        'additional_data',
        'required_documents',
        'target_location',
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
        return $this->belongsTo(Employee::class);
    }

    // علاقة مع الموافقات المرتبطة بالطلب
    // public function approvals()
    // {
    //     return $this->hasMany(RequestApproval::class);
    // }
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
    \Log::info('Approver Role:', ['user_role' => $approver->role]);
    \Log::info('Request Current Approver Role:', ['current_approver_role' => $this->current_approver_role]);
    
    // التحقق من أن المسؤول الحالي لديه نفس الدور المطلوب
    $approverRoleName = $approver->role->name;

    // التحقق من أن المسؤول الحالي لديه نفس الدور المطلوب
    if (strtolower($approverRoleName) !== strtolower($this->current_approver_role)) {
        throw new \Exception(__('You are not authorized to approve this request. Your role: :role, Required role: :required_role', [
            'role' => $approverRoleName,
            'required_role' => $this->current_approver_role,
        ]));
    }

    // التحقق مما إذا كان المسؤول قد وافق مسبقًا
    $existingApproval = $this->approvals()
        ->where('approver_id', $approver->id)
        ->where('approver_role', $approver->role)
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
    if ($this->type === 'leave' && $approvalFlow->conditions['min_balance'] ?? false) {
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
    $currentRole = $this->current_approver_role;
    $nextApprovalFlow = Role::where('level', '>', Role::where('name', $currentRole)->first()->level)
        ->orderBy('level', 'asc')
        ->first();

    if ($nextApprovalFlow) {
        // إذا كان هناك مستوى موافقة آخر
        $this->current_approver_role = $nextApprovalFlow->name;
    } else {
        // إذا انتهت جميع المستويات
        $this->current_approver_role = null;
        $this->status = 'approved';
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
            new RequestStatusNotification($this, 'approved', $approver)
        );
    }
}



public function rejectRequest($approver, $comments = null)
{
    // تحديث حالة الطلب إلى "مرفوض"
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

    // إشعار الموظف بالرفض
    $this->employee->notify(
        new RequestStatusNotification($this, 'rejected', $approver, $comments)
    );
}



}
