<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeProjectRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'project_id',
        'start_date',
        'end_date',
        'zone_id',
        'shift_id',
        'status',
    ];

    // علاقة مع الموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
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

   
    // استرجاع المنطقة المرتبطة بالسجل
    $zone = $this->zone;

    if (!$zone || !$zone->pattern) {
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

    // تاريخ البداية
    // $startDate = Carbon::parse($this->start_date);
    $startDate = Carbon::parse($this->shift->start_date);

    // عدد الأيام منذ تاريخ البداية
    $daysSinceStart = $startDate->diffInDays(Carbon::today());

    // حساب اليوم الحالي داخل الدورة
    $currentDayInCycle = $daysSinceStart % $cycleLength;

    // إذا كان اليوم الحالي أقل من عدد أيام العمل، فهو يوم عمل
    return $currentDayInCycle < $workingDays;
}



}
