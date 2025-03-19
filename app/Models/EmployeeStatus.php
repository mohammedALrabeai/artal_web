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
    ];

    /**
     * تحويل الحقول إلى أنواع معينة.
     */
    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_gps_status_at' => 'datetime',
        'gps_enabled' => 'boolean',
        'last_location' => 'array', // يمكن تخزين الإحداثيات كـ JSON
    ];

    /**
     * علاقة الحالة بالموظف.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
