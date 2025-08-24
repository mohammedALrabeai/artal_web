<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRenewal extends Model
{
    use HasFactory;

    protected $table = 'attendance_renewals';

    protected $fillable = [
        'attendance_id',
        'renewed_at',
        'kind',     // manual | auto | voice (قيم مقترحة فقط)
        'status',   // ok | canceled | expired (قيم مقترحة فقط)
        'payload',
    ];

    protected $casts = [
        'renewed_at' => 'datetime',
        'payload'    => 'array',
    ];

    // العلاقة الأساسية
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * عند إنشاء أي تجديد:
     * - نحدّث employee_statuses.last_present_at (إذا كان سجل الحالة موجودًا)
     * ملاحظة: لا ننشئ سجل حالة جديد تلقائيًا.
     */
     protected static function booted(): void
    {
        static::created(function (AttendanceRenewal $renewal) {
            $attendance = $renewal->attendance()->select('employee_id')->first();
            if (!$attendance || empty($attendance->employee_id)) {
                return;
            }

            // ✅ تحديث لحظي لحقل آخر تجديد وأيضًا آخر حضور كما كان لديك
            \App\Models\EmployeeStatus::query()
                ->where('employee_id', $attendance->employee_id)
                ->update([
                    'last_present_at' => now(),
                    'last_renewal_at' => now(),  // ✅ سطرنا الجديد
                ]);
        });
    }
}
