<?php

namespace App\Models;

use App\Notifications\RequestStatusNotification;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
        'exclusion_id',
        'coverage_id',
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

    // public function exclusion()
    // {
    //     return $this->hasOne(\App\Models\Exclusion::class);
    // }

    public function approvals()
    {
        return $this->hasMany(RequestApproval::class)
            ->orderBy('approved_at', 'asc'); // ترتيب الموافقات حسب الوقت
    }

    public function exclusion()
    {
        return $this->belongsTo(Exclusion::class, 'exclusion_id');
    }

    public function coverage()
    {
        return $this->belongsTo(Coverage::class, 'coverage_id');
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

        if ($approvals->every(fn ($approval) => $approval->status === 'approved')) {
            $this->status = 'approved'; // إذا تمت الموافقة على جميع المستويات
        } elseif ($approvals->contains(fn ($approval) => $approval->status === 'rejected')) {
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
        // 🔹 التحقق من أن حالة الطلب تسمح بالموافقة
        if ($this->status !== 'pending') {
            Notification::make()
                ->title(__('approval_error'))
                ->body(__('approval_status', ['status' => $this->status]))
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'approval_status' => __('This request cannot be approved as it is already :status.', ['status' => $this->status]),
            ]);
        }

        \Log::info('Approver Roles:', ['user_roles' => $approver->getRoleNames()]);
        \Log::info('Request Current Approver Role:', ['current_approver_role' => $this->current_approver_role]);

        // 🔹 جلب جميع المستويات المتاحة لهذا الطلب من `approval_flows`
        $approvalLevels = $this->approvalFlows()->orderBy('approval_level', 'asc')->get();

        // 🔹 استخراج جميع الأدوار المتاحة للموافقة على هذا الطلب
        $validApproverRoles = $approvalLevels->pluck('approver_role')->toArray();

        // 🔹 جلب جميع أدوار المستخدم الذي يقوم بالموافقة
        $approverRoles = $approver->getRoleNames()->toArray();

        // 🔹 التحقق مما إذا كان المستخدم لديه أحد الأدوار في `approval_flows`
        $matchingRoles = array_intersect(array_map('strtolower', $approverRoles), array_map('strtolower', $validApproverRoles));

        if (empty($matchingRoles)) {
            Notification::make()
                ->title(__('unauthorized_approval'))
                ->body(__('approver_roles', [
                    'roles' => implode(', ', $approverRoles),
                    'required_roles' => implode(', ', $validApproverRoles),
                ]))
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'current_approver_role' => __('You are not authorized to approve this request. Your roles: :roles, Required roles: :required_roles', [
                    'roles' => implode(', ', $approverRoles),
                    'required_roles' => implode(', ', $validApproverRoles),
                ]),
            ]);
        }

        // 🔹 جلب المستوى الحالي للموافقة من `approval_flows`
        $currentApprovalLevel = $approvalLevels->where('approver_role', $this->current_approver_role)->first()?->approval_level;

        // 🔹 جلب أعلى مستوى يملكه المستخدم داخل `approval_flows`
        $userHighestLevel = $approvalLevels->whereIn('approver_role', $approverRoles)->max('approval_level');

        // 🔹 إذا كان المستخدم في مستوى أعلى، يتم تجاوز المستويات الأقل
        $nextApprovalFlow = $approvalLevels->where('approval_level', '>', min($currentApprovalLevel, $userHighestLevel))
            ->sortBy('approval_level')
            ->first();

        if ($nextApprovalFlow) {
            // ✅ إذا كان هناك مستوى أعلى يجب الانتقال إليه
            $this->current_approver_role = $nextApprovalFlow->approver_role;
        } else {
            // إذا انتهت جميع المستويات، يتم الموافقة النهائية على الطلب
            $this->current_approver_role = null;
            $this->status = 'approved';

            // ✅ تحديث حالة الإجازة إذا كانت الموافقة نهائية
            if ($this->type === 'leave' && $this->leave) {
                $this->leave->update([
                    'approved' => true,
                ]);
                // إنشاء سجل الإجازة في جدول التحضير
                $this->makeLeaveAttendance();
            }

            // ✅ تحديث حالة الاستبعاد إذا كان الطلب من نوع `exclusion`
            if ($this->type === 'exclusion' && $this->exclusion) {
                $this->exclusion->update([
                    'status' => Exclusion::STATUS_APPROVED,
                ]);
            }
            if ($this->type === 'coverage' && $this->coverage) {
                $this->coverage->update([
                    'status' => 'completed',
                ]);

                // ✅ تحديث حالة الحضور المرتبط بالتغطية إلى "approved"
                $this->coverage->attendance()->update([
                    'approval_status' => 'approved',
                ]);

            }
        }

        $this->save();

        // ✅ تسجيل الموافقة في `request_approvals`
        $this->approvals()->create([
            'approver_id' => $approver->id,
            'approver_role' => implode(', ', $matchingRoles), // حفظ الدور الذي تمت الموافقة به
            'status' => 'approved',
            'approved_at' => now(),
            'notes' => $comments,
        ]);

        // ✅ إرسال إشعار الموافقة النهائية
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
            'notes' => $comments,
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
        if ($this->type === 'leave' && false) {    // يلزم تفاصيل عن نوع الاجازة
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
        } elseif ($this->type === 'coverage' && $this->coverage) {

            // ✅ تحديث حالة التغطية إلى "rejected"
            $this->coverage->update([
                'status' => 'cancelled',
            ]);

            // ✅ تحديث حالة الحضور المرتبط بالتغطية إلى "rejected"
            $this->coverage->attendance()->update([
                'approval_status' => 'rejected',
            ]);
        }

        // // إشعار الموظف بالرفض
        // $this->employee()->notify(
        //     new RequestStatusNotification($this, 'rejected', $approver, $comments)
        // );
        \Log::info('Notification sent successfully.');

    }

    public function makeLeaveAttendance()
    {
        // التحقق من وجود الإجازة
        if (! $this->leave) {
            \Log::error('Leave record not found for this request.', [
                'request_id' => $this->id,
            ]);

            return;
        }

        // جلب تواريخ البداية والنهاية
        $startDate = \Carbon\Carbon::parse($this->leave->start_date);
        $endDate = \Carbon\Carbon::parse($this->leave->end_date);

        // التحقق من الموظف
        if (! $this->employee) {
            \Log::error('Employee not found for this request.', [
                'request_id' => $this->id,
            ]);

            return;
        }

        // جلب سجل المشروع الحالي للموظف
        $projectRecord = $this->employee->currentProjectRecord;

        if (! $projectRecord || ! $projectRecord->zone || ! $projectRecord->shift) {
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
        if (! $startDate || ! $endDate) {
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
                if (! $isWorkingDay) {
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
                        'ismorning' => true,
                        'status' => 'leave', // حالة الحضور "إجازة"
                        'notes' => 'Leave: '.$this->leave->id.' - request ID: '.$this->id.': '.$this->leave->type.' - '.$this->leave->reason.' - '.$this->leave->start_date.' - '.$this->leave->end_date, // ملاحظات
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

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'model');
    }
}
