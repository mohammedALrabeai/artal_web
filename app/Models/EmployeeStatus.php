<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


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

             'is_stationary',      // هل الجهاز ساكن
        'last_movement_at',   // آخر وقت تحرك فيه
          'last_renewal_at',
           'last_renewal_at'      => 'datetime',

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

      'is_stationary' => 'boolean',
        'last_movement_at' => 'datetime',

    ];

    /**
     * علاقة الحالة بالموظف.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
     // (اختياري) سكوب سريع للي ما جدّدوا خلال X دقيقة
    public function scopeRenewalOverdue($query, int $minutes)
    {
        return $query->where(function ($q) use ($minutes) {
            $q->whereNull('last_renewal_at')
              ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_renewal_at, NOW()) > ?', [$minutes]);
        });
    }

    // (اختياري) أكسسور يحسب الدقائق منذ آخر تجديد
    public function getMinutesSinceLastRenewalAttribute(): ?int
    {
        if (!$this->last_renewal_at instanceof Carbon) return null;
        return $this->last_renewal_at->diffInMinutes(now());
    }
}
