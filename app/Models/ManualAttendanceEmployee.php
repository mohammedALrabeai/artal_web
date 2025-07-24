<?php

namespace App\Models;
// app/Models/ManualAttendanceEmployee.php



use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualAttendanceEmployee extends Model
{
    protected $fillable = [
        'employee_project_record_id',
        'attendance_month',
    ];

   // app/Models/ManualAttendanceEmployee.php

public function projectRecord(): BelongsTo
{
    return $this->belongsTo(EmployeeProjectRecord::class, 'employee_project_record_id');
}


    public function attendances(): HasMany
    {
        return $this->hasMany(ManualAttendance::class);
    }
}
