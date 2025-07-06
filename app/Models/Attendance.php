<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Attendance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'zone_id',
        'shift_id',

        'date',
        'ismorning',
        'check_in',
        'check_in_datetime',
        'check_out',
        'check_out_datetime',
        'status',
        'work_hours', 'notes', 'is_late',
        'approval_status',
        'coverage_id',
        'is_coverage',
        'out_of_zone',
        'auto_checked_out', // جديد: حقل لتحديد ما إذا كان الخروج تلقائيًا
    ];

    protected $casts = [
        // 'check_in_datetime' => 'datetime',
        // 'check_out_datetime' => 'datetime',
        // 'date' => 'date',
        'ismorning' => 'boolean',
        'is_late' => 'boolean',
        'is_coverage' => 'boolean',
        'out_of_zone' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // ✅ علاقة الحضور مع التغطية (إذا كان هناك سجل تغطية مرتبط بالحضور)
    public function coverage()
    {
        return $this->belongsTo(Coverage::class, 'coverage_id');
    }

    //   // تفعيل تسجيل التغييرات
    //   protected static $logAttributes = ['*'];
    //   protected static $logOnlyDirty = true;
    //   protected static $submitEmptyLogs = false;
    //   protected static $logName = 'attendance';

    // آخر سجل نشط (بدون انصراف)
public function scopeActiveToday($q, int $employeeId)
{
    return $q->where('employee_id', $employeeId)
             ->whereDate('date', now('Asia/Riyadh'))
             ->whereNull('check_out')
             ->latest('check_in_datetime');
}

// أي سجل (نشط أو منتهٍ) في اليوم الحالي
public function scopeLastToday($q, int $employeeId)
{
    return $q->where('employee_id', $employeeId)
             ->whereDate('date', now('Asia/Riyadh'))
             ->latest('check_in_datetime');
}


    public function getDescriptionForEvent(string $eventName): string
    {
        return "Attendance record has been {$eventName}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // تسجيل جميع الحقول
            ->logOnlyDirty() // تسجيل الحقول التي تغيرت فقط
            ->dontSubmitEmptyLogs(); // تجاهل التعديلات الفارغة
    }
}
