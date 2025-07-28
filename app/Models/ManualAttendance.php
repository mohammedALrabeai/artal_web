<?php

// app/Models/ManualAttendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualAttendance extends Model
{
    protected $fillable = [
        'manual_attendance_employee_id',
        'date',
        'status',
        'has_coverage_shift',
        'notes',
        'updated_by',
        'coverage_employee_id'
    ];

    public function attendanceEmployee(): BelongsTo
    {
        return $this->belongsTo(ManualAttendanceEmployee::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
      public function coverageEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coverage_employee_id');
    }
}
