<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeStatus extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة.
     */
    protected $fillable = [
        'employee_id',
        'last_seen_at',
        'gps_enabled',
        'last_gps_status_at',
        'last_location',
        'is_inside',
        'notification_enabled',

        'consecutive_absence_count',
        'last_present_at',
        'exclude_from_absence_report',

    ];

    /**
     * تحويل الحقول إلى أنواع معينة.
     */
    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_gps_status_at' => 'datetime',
        'gps_enabled' => 'boolean',
        'is_inside' => 'boolean',
        'notification_enabled' => 'boolean',
        'last_location' => 'array', // يمكن تخزين الإحداثيات كـ JSON

        'last_present_at' => 'date',
        'exclude_from_absence_report' => 'boolean',

    ];

    /**
     * علاقة الحالة بالموظف.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
