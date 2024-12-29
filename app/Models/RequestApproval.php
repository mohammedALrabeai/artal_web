<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'approver_id',
        'approver_type',
        'status',
        'notes',
        'approved_at',
    ];


    public function approvalFlow()
{
    return $this->belongsTo(ApprovalFlow::class, 'request_type', 'request_type');
}

public function updateApprovalStatus($status, $comments = null)
{
    $this->status = $status; // تحديث الحالة (موافقة/رفض)
    $this->comments = $comments; // إضافة الملاحظات (إن وجدت)
    $this->approved_at = now(); // تسجيل وقت الموافقة
    $this->save();

    // تحديث حالة الطلب الأساسي
    $this->request->updateRequestStatus();
}


    // علاقة مع الطلب
    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    // علاقة مع المستخدم الذي يوافق
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
