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
        'actual_zone_id',
        'is_main'
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

    public function actualZone()
{
    return $this->belongsTo(\App\Models\Zone::class, 'actual_zone_id');
}

}
