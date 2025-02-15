<?php

namespace App\Models;

use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Shift extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'name',
        'zone_id',
        'type',
        'morning_start',
        'morning_end',
        'evening_start',
        'evening_end',
        'early_entry_time',
        'last_entry_time',
        'early_exit_time',
        'last_time_out',
        'start_date',
        'emp_no',
        'status',
    ];

    // علاقة مع المواقع
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    // تخزين القيم بالدقائق كوقت
    public function setEarlyEntryTimeAttribute($value)
    {
        $this->attributes['early_entry_time'] = gmdate('H:i:s', $value * 60);
    }

    public function setLastEntryTimeAttribute($value)
    {
        $this->attributes['last_entry_time'] = gmdate('H:i:s', $value * 60);
    }

    public function setEarlyExitTimeAttribute($value)
    {
        $this->attributes['early_exit_time'] = gmdate('H:i:s', $value * 60);
    }

    public function setLastTimeOutAttribute($value)
    {
        $this->attributes['last_time_out'] = gmdate('H:i:s', $value * 60);
    }

    public function getEarlyEntryTimeAttribute($value)
    {
        if ($value) {
            $parts = explode(':', $value); // فصل الساعات والدقائق والثواني

            return ($parts[0] * 60) + $parts[1]; // حساب الدقائق (الساعات × 60) + الدقائق
        }

        return null;
    }

    public function getLastEntryTimeAttribute($value)
    {
        if ($value) {
            $parts = explode(':', $value);

            return ($parts[0] * 60) + $parts[1];
        }

        return null;
    }

    public function getEarlyExitTimeAttribute($value)
    {
        if ($value) {
            $parts = explode(':', $value);

            return ($parts[0] * 60) + $parts[1];
        }

        return null;
    }

    public function getLastTimeOutAttribute($value)
    {
        if ($value) {
            $parts = explode(':', $value);

            return ($parts[0] * 60) + $parts[1];
        }

        return null;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'shift_id');
    }

    public function isWorkingDay()
    {
        // استرجاع المنطقة المرتبطة بالوردية
        $zone = $this->zone;

        if (! $zone || ! $zone->pattern) {
            // إذا لم تكن هناك بيانات كافية
            return null;
        }

        $pattern = $zone->pattern;

        $workingDays = $pattern->working_days;
        $offDays = $pattern->off_days;

        // التأكد من وجود بيانات صالحة
        if ($workingDays === null || $offDays === null || $workingDays <= 0) {
            return null;
        }

        // دورة العمل = عدد أيام العمل + الإجازة
        $cycleLength = $workingDays + $offDays;

        // تاريخ بداية الوردية
        $startDate = Carbon::parse($this->start_date);

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays(Carbon::today());

        // حساب اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // إذا كان اليوم الحالي أقل من عدد أيام العمل، فهو يوم عمل
        return $currentDayInCycle < $workingDays;
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if ($eventName === 'updated') {
            $notificationService = new NotificationService;
            $editedBy = auth()->user()->name;
            $shift = $this;

            // مقارنة الحقول القديمة بالجديدة
            $changes = $shift->getChanges();
            $original = $shift->getOriginal();
            $ignoredFields = ['updated_at', 'created_at'];
            $changeDetails = '';

            foreach ($changes as $field => $newValue) {
                if (! in_array($field, $ignoredFields) && isset($original[$field]) && $original[$field] !== $newValue) {
                    $changeDetails .= ucfirst(str_replace('_', ' ', $field))
                        .": \"{$original[$field]}\" → \"{$newValue}\"\n";
                }
            }

            // الحصول على اسم المنطقة المرتبطة بالوردية إن وجدت
            $zoneName = isset($shift->zone) ? $shift->zone->name : 'غير متوفر';

            $message = "تم تعديل بيانات الوردية بنجاح\n\n";
            $message .= "الوردية: {$shift->name}\n";
            $message .= "الموقع: {$zoneName}\n";
            $message .= "تم التعديل بواسطة: {$editedBy}\n\n";
            $message .= "تفاصيل التعديل:\n";
            $message .= ! empty($changeDetails) ? $changeDetails : "⚠️ لم يتم الكشف عن تغييرات كبيرة.\n";

            $notificationService->sendNotification(
                ['manager', 'general_manager', 'hr'],
                'تعديل بيانات الوردية',
                $message,
                [
                    $notificationService->createAction('عرض بيانات الوردية', "/admin/shifts/{$shift->id}/edit", 'heroicon-s-eye'),
                    $notificationService->createAction('قائمة الورديات', '/admin/shifts', 'heroicon-s-users'),
                ]
            );
        }

        return "Shift record has been {$eventName}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // تسجيل جميع الحقول
            ->logOnlyDirty() // تسجيل الحقول التي تغيرت فقط
            ->dontSubmitEmptyLogs(); // تجاهل التعديلات الفارغة
    }
}
