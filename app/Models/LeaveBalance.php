<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'balance',
        'accrued_per_month',
            'annual_leave_days', // الحقل الجديد'
        'used_balance',
        'last_updated',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function calculateAnnualLeaveBalance()
    {
        // التحقق من أن الموظف مرتبط بهذا الرصيد
        $employee = $this->employee;
        if (!$employee || !$employee->actual_start) {
            return 0;
        }
    
        // عدد أيام الإجازة السنوية من الجدول
        $annualLeaveDays = $this->annual_leave_days;
    
        // تاريخ مباشرة العمل
        $startDate = Carbon::parse($employee->actual_start);
    
        // تاريخ اليوم
        $currentDate = Carbon::now();
    
        // عدد الأيام بين تاريخ البدء واليوم الحالي
        $daysWorked = $startDate->diffInDays($currentDate);
    
    // حساب عدد الأيام المسجلة بحالة إجازة مدفوعة أو غير مدفوعة
    $leaveDays = Attendance::where('employee_id', $this->employee_id)
        ->whereDate('date', '>=', $startDate)
        ->whereIn('status', ['leave', 'UV']) // الحالات المعنية
        ->count();

    // عدد أيام السنة ناقص أيام الإجازات المسجلة
    $daysInYear = 365 - $leaveDays;
    
        // حساب الرصيد السنوي بناءً على عدد الأيام التي عملها الموظف
        $calculatedBalance = ($annualLeaveDays / $daysInYear) * $daysWorked;
        
        // الرصيد المتبقي بعد استهلاك الإجازات
        $remainingBalance = $calculatedBalance - $this->used_balance;
        
        // إرجاع القيمة كعدد صحيح
    return max(intval($remainingBalance), 0);// التأكد من أن الرصيد لا يكون سالبًا
    }
    
}
