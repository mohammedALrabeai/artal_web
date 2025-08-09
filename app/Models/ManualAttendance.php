<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualAttendance extends Model
{
    /*********************************************************
     * الأعمدة القابلة للملء
     *********************************************************/
    protected $fillable = [
        'manual_attendance_employee_id',
        'date',
        'status',
        'is_coverage',
        'notes',
        'created_by',
        // 'actual_zone_id',
        // 'actual_shift_id',
        'replaced_employee_project_record_id',
    ];

    /*********************************************************
     * تحويل الأنواع (Casts)
     *********************************************************/
    protected $casts = [
        'date'          => 'date',     // تُعاد ككائن Carbon
        'is_coverage'   => 'boolean',  // true / false
    ];

    /*********************************************************
     * العلاقات
     *********************************************************/
    public function monthlyRecord(): BelongsTo
    {
        return $this->belongsTo(
            ManualAttendanceEmployee::class,
            'manual_attendance_employee_id'
        );
    }

    public function actualZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'actual_zone_id');
    }

    public function actualShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'actual_shift_id');
    }

    public function replacedRecord(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeProjectRecord::class,
            'replaced_employee_project_record_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
