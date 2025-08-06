<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class EmployeeProjectRecord extends Model
{
    use HasFactory;
    use LogsActivity;


    protected $fillable = [
        'employee_id',
        'project_id',
        'start_date',
        'end_date',
        'zone_id',
        'shift_id',
        'status',
        'assigned_by',
        'shift_slot_id'
    ];

    // علاقة مع الموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (auth()->check()) {
                $record->assigned_by = auth()->id();
            }
        });

        static::created(function (self $record) {
            $month = Carbon::now('Asia/Riyadh')->startOfMonth()->toDateString();

            $alreadyExists = ManualAttendanceEmployee::where('employee_project_record_id', $record->id)
                ->where('attendance_month', $month)
                ->exists();

            if (! $alreadyExists) {
                ManualAttendanceEmployee::create([
                    'employee_project_record_id' => $record->id,
                    'attendance_month' => $month,
                ]);
            }
        });

        // عند التحديث: إذا تغيّر status من true إلى false → نحدّث end_date
        // static::saving(function (self $record) {
        //     if (
        //         $record->isDirty('status') &&
        //         $record->getOriginal('status') === true &&
        //         $record->status === false &&
        //         is_null($record->end_date)
        //     ) {
        //         $record->end_date = now('Asia/Riyadh');
        //     }
        // });
    }



    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function shiftSlot()
    {
        return $this->belongsTo(ShiftSlot::class);
    }


    // علاقة مع المشروع
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function isWorkingDay()
    {

        $zone = $this->zone;

        if (! $zone || ! $zone->pattern) {
            return null;
        }

        $pattern = $zone->pattern;

        $workingDays = $pattern->working_days;
        $offDays = $pattern->off_days;

        if ($workingDays === null || $offDays === null || $workingDays <= 0) {
            return null;
        }

        $cycleLength = $workingDays + $offDays;


        $startDate = Carbon::parse($this->shift->start_date);

        $daysSinceStart = $startDate->diffInDays(Carbon::today());

        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // إذا كان اليوم الحالي أقل من عدد أيام العمل، فهو يوم عمل
        return $currentDayInCycle < $workingDays;
    }

    // app/Models/EmployeeProjectRecord.php

    public function scopeActiveNonExcluded($query)
    {
        return $query
            ->where('status', true)
            ->where('start_date', '<=', now('Asia/Riyadh'))
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now('Asia/Riyadh'));
            })
            ->whereHas(
                'shift',
                fn($q) => $q->where('exclude_from_auto_absence', false)
            );
    }

    // داخل EmployeeProjectRecord.php
public function scopeActive($q)
{
    return $q->where('status', true)->whereNull('end_date');
}


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // سجل جميع الحقول
            ->logOnlyDirty() // فقط الحقول المتغيرة
            ->dontSubmitEmptyLogs(); // تجاهل إذا لم يتغير شيء فعلياً
    }
}
