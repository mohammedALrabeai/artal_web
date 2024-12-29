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
        'current_approver_id',
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
    public function approvals()
    {
        return $this->hasMany(RequestApproval::class);
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

public function approveRequest($approver)
{
    $currentLevel = $this->approvals->max('approval_level');
    $nextLevel = $this->approvalFlows
        ->where('approval_level', $currentLevel + 1)
        ->first();

    if (!$nextLevel) {
        $this->status = 'approved'; // إذا لم تكن هناك مستويات أخرى
    } else {
        $this->current_approver_id = $nextLevel->approver_role;
    }

    $this->save();

    // تسجيل الموافقة
    $this->approvals()->create([
        'approver_id' => $approver->id,
        'approver_type' => get_class($approver), // تحديد نوع الموافق
        'status' => 'approved',
        'approved_at' => now(),
    ]);
}

public function rejectRequest($approver, $comments = null)
{
    $this->status = 'rejected';
    $this->save();

    // تسجيل الرفض
    $this->approvals()->create([
        'approver_id' => $approver->id,
        'status' => 'rejected',
        'approved_at' => now(),
        'comments' => $comments,
    ]);
}


}
