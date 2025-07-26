<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'leave_type_id',
        'reason',
        'approved',
        'employee_project_record_id'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function substitutes()
{
    return $this->hasMany(LeaveSubstitute::class);
}


    public function employeeProjectRecord()
{
    return $this->belongsTo(EmployeeProjectRecord::class, 'employee_project_record_id');
}


    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function request()
    {
        return $this->hasOne(Request::class, 'leave_id');
    }

    // ✅ للحصول على هل هي مدفوعة
    public function getIsPaidAttribute(): bool
    {
        return $this->leaveType?->is_paid ?? false;
    }

    // ✅ للحصول على الكود المخصص للنوع
    public function getCodeAttribute(): ?string
    {
        return $this->leaveType?->code;
    }

    // ✅ الاسم الكامل لنوع الإجازة
    public function getTypeNameAttribute(): ?string
    {
        return $this->leaveType?->name;
    }
}
