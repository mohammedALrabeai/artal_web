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

    public function employeeProjectRecords()
    {
        return $this->hasMany(\App\Models\EmployeeProjectRecord::class, 'shift_id');
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

    public function isWorkingDay2(?Carbon $referenceDateTime = null): ?bool
    {
        $referenceDateTime = $referenceDateTime ? $referenceDateTime->copy()->tz('Asia/Riyadh') : Carbon::now('Asia/Riyadh');

        $zone = $this->zone;
        if (! $zone || ! $zone->pattern) {
            return null;
        }

        $pattern = $zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;

        if ($workingDays <= 0 || $offDays < 0) {
            return null;
        }

        $cycleLength = $workingDays + $offDays;
        if ($cycleLength <= 0) {
            return null;
        }

        // تاريخ ووقت بداية الدورة (مع مراعاة وقت الوردية)
        $startDate = Carbon::parse($this->start_date, 'Asia/Riyadh');

        // إذا كانت الوردية مسائية وتمتد إلى اليوم التالي:
        if ($this->type === 'evening' || $this->type === 'evening_morning') {
            $startDate->subHours(4); // تعديل لاحتساب بداية الدورة من الليلة السابقة
        }

        // الفرق بالأيام مع مراعاة الوقت الدقيق
        $daysSinceStart = $startDate->diffInDays($referenceDateTime, false);

        // إذا كان التاريخ المرجعي قبل بداية الدورة
        if ($daysSinceStart < 0) {
            return false;
        }

        $currentDayInCycle = $daysSinceStart % $cycleLength;

        return $currentDayInCycle < $workingDays;
    }

    public function isWorkingDayDynamic(Carbon $referenceDateTime): bool
    {
        $zone = $this->zone;
        if (! $zone || ! $zone->pattern) {
            return false;
        }

        $pattern = $zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;

        if ($workingDays <= 0 || $offDays < 0) {
            return false;
        }

        $cycleLength = $workingDays + $offDays;
        if ($cycleLength <= 0) {
            return false;
        }

        // تحديد تاريخ بداية الوردية
        $startDate = Carbon::parse($this->start_date, 'Asia/Riyadh');

        // تعديل تاريخ البداية للورديات المسائية التي تمتد عبر منتصف الليل
        if ($this->type === 'evening' || $this->type === 'evening_morning') {
            $eveningStart = Carbon::parse($this->evening_start, 'Asia/Riyadh');
            if ($eveningStart->hour >= 12 && $referenceDateTime->hour < 12) {
                $startDate->subDay(); // نعتبرها من اليوم السابق إذا كنا في الصباح الباكر
            }
        }

        // حساب الفرق بالأيام وتحديد موقع اليوم في الدورة
        $daysSinceStart = $startDate->diffInDays($referenceDateTime->copy()->startOfDay());
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        return $currentDayInCycle < $workingDays;
    }

    public function isCurrentlyActiveV2(Carbon $now): bool
    {
        $pattern = $this->zone?->pattern;

        if (! $pattern || ! $this->start_date) {
            return false;
        }

        // ✅ استخدام الدالة الموثوقة للتحقق من يوم عمل
        if (! $this->isWorkingDay2($now)) {
            return false;
        }

        $cycleLength = $pattern->working_days + $pattern->off_days;
        if ($cycleLength <= 0) {
            return false;
        }

        // ✅ حساب رقم الدورة والفردية/الزوجية
        $startDate = Carbon::parse($this->start_date)->startOfDay('Asia/Riyadh');
        $daysSinceStart = $startDate->diffInDays($now->copy()->startOfDay());
        $cycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;
        $isOddCycle = $cycleNumber % 2 === 1;

        // ✅ تواريخ اليوم والأمس
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();

        // ✅ دالة مساعدة لفحص الفترات الزمنية
        $checkShift = function (Carbon $baseDate, $startTime, $endTime) use ($now) {
            $start = Carbon::parse("{$baseDate->toDateString()} {$startTime}", 'Asia/Riyadh');
            $end = Carbon::parse("{$baseDate->toDateString()} {$endTime}", 'Asia/Riyadh');

            // ✅ إذا كانت الوردية تمتد بعد منتصف الليل
            if ($end->lessThan($start)) {
                $end->addDay();
            }

            return $now->between($start, $end);
        };

        // ✅ التحقق حسب نوع الوردية
        switch ($this->type) {
            case 'morning':
                return $checkShift($today, $this->morning_start, $this->morning_end);

            case 'evening':
                // ✅ جرّب اليوم الحالي
                if ($checkShift($today, $this->evening_start, $this->evening_end)) {
                    return true;
                }

                // ✅ إذا كانت الوردية تمتد بعد منتصف الليل، جرّب أمس أيضًا
                if ($this->evening_end < $this->evening_start) {
                    return $checkShift($yesterday, $this->evening_start, $this->evening_end);
                }

                return false;

            case 'evening_morning':
                if ($isOddCycle) {
                    // ✅ الدورة الفردية = مساء
                    if ($checkShift($today, $this->evening_start, $this->evening_end)) {
                        return true;
                    }

                    return $checkShift($yesterday, $this->evening_start, $this->evening_end);
                } else {
                    // ✅ الدورة الزوجية = صباح
                    return $checkShift($today, $this->morning_start, $this->morning_end);
                }

            case 'morning_evening':
                if ($isOddCycle) {
                    // ✅ الدورة الفردية = صباح
                    return $checkShift($today, $this->morning_start, $this->morning_end);
                } else {
                    // ✅ الدورة الزوجية = مساء
                    if ($checkShift($today, $this->evening_start, $this->evening_end)) {
                        return true;
                    }

                    return $checkShift($yesterday, $this->evening_start, $this->evening_end);
                }

            default:
                return false;
        }
    }

    // ✅ دالة لحساب نوع الوردية الحالية (صباح / مساء)
    // echo $shift->shift_type; // سيطبع 1 إذا كانت صباحية، أو 2 إذا كانت مسائية
    public function getShiftTypeAttribute()
    {
        // ✅ التحقق من وجود المنطقة ونمط العمل
        if (! $this->zone || ! $this->zone->pattern) {
            return null;
        }

        // ✅ الحصول على عدد أيام العمل وأيام الراحة
        $workingDays = $this->zone->pattern->working_days;
        $offDays = $this->zone->pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        if ($cycleLength <= 0) {
            return null; // تجنب القسمة على صفر
        }

        // ✅ حساب عدد الأيام منذ بداية الوردية
        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $totalDays = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));

        // ✅ حساب رقم الدورة الحالية
        $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

        // ✅ تحديد نوع الوردية بناءً على دورة العمل
        switch ($this->type) {
            case 'morning':
                return 1; // صباح

            case 'evening':
                return 2; // مساء

            case 'morning_evening':
                return $cycleNumber % 2 == 1 ? 1 : 2; // صباح في الدورة الفردية، مساء في الزوجية

            case 'evening_morning':
                return $cycleNumber % 2 == 1 ? 2 : 1; // مساء في الدورة الفردية، صباح في الزوجية

            default:
                return null; // غير معروف
        }
    }

    public function isCurrent(?Carbon $currentTime = null): bool
    {
        // استخدام التوقيت الحالي إذا لم يتم توفيره
        $currentTime = $currentTime ?: Carbon::now('Asia/Riyadh');

        // تحقق من إذا كان اليوم يوم عمل
        if (! $this->isWorkingDay()) {
            return false;
        }

        $today = $currentTime->toDateString();

        // إنشاء أوقات الوردية
        $morningStart = Carbon::parse("$today {$this->morning_start}", 'Asia/Riyadh');
        $morningEnd = Carbon::parse("$today {$this->morning_end}", 'Asia/Riyadh');
        $eveningStart = Carbon::parse("$today {$this->evening_start}", 'Asia/Riyadh');
        $eveningEnd = Carbon::parse("$today {$this->evening_end}", 'Asia/Riyadh');

        // تعديل الوقت إذا تجاوز منتصف الليل
        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
        }

        // تحديد نوع الوردية
        switch ($this->type) {
            case 'morning':
                return $currentTime->between($morningStart, $morningEnd);

            case 'evening':
                return $currentTime->between($eveningStart, $eveningEnd);

            case 'morning_evening':
            case 'evening_morning':
                return $this->checkShiftCycle($currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd);

            default:
                return false;
        }
    }

    protected function checkShiftCycle(
        Carbon $currentTime,
        Carbon $morningStart,
        Carbon $morningEnd,
        Carbon $eveningStart,
        Carbon $eveningEnd
    ): bool {
        if (! $this->zone || ! $this->zone->pattern) {
            return false;
        }

        $pattern = $this->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            throw new \Exception('Invalid cycle length');
        }

        $startDate = Carbon::parse($this->start_date, 'Asia/Riyadh')->startOfDay();
        $daysSinceStart = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));
        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;
        $currentDayInCycle = $daysSinceStart % $cycleLength;
        $isWorkingDay = $currentDayInCycle < $pattern->working_days;
        $isOddCycle = $currentCycleNumber % 2 === 1;

        if (! $isWorkingDay) {
            return false;
        }

        if ($this->type === 'morning_evening') {
            return ($isOddCycle && $currentTime->between($morningStart, $morningEnd)) ||
                   (! $isOddCycle && $currentTime->between($eveningStart, $eveningEnd));
        }

        if ($this->type === 'evening_morning') {
            return ($isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) ||
                   (! $isOddCycle && $currentTime->between($morningStart, $morningEnd));
        }

        return false;
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
