<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Shift;
use Carbon\Carbon;
use App\Models\Zone;
use App\Models\Project;
use Exception;

class AreaController extends Controller
{
    public function getAreasWithDetails()
    {
        // تعيين التوقيت إلى توقيت الرياض
        $currentTime = Carbon::now('Asia/Riyadh');

        $areas = Area::with(['projects.zones.shifts.attendances'])->get();

        $data = $areas->map(function ($area) use ($currentTime) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($currentTime) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'zones' => $project->zones->map(function ($zone) use ($currentTime) {
                            // الشفت الحالي
                            $currentShift = $zone->shifts->filter(function ($shift) use ($currentTime) {
                                $morningStart = Carbon::createFromTimeString($shift->morning_start, 'Asia/Riyadh');
                                $morningEnd = Carbon::createFromTimeString($shift->morning_end, 'Asia/Riyadh');
                                $eveningStart = Carbon::createFromTimeString($shift->evening_start, 'Asia/Riyadh');
                                $eveningEnd = Carbon::createFromTimeString($shift->evening_end, 'Asia/Riyadh');

                                return ($currentTime->between($morningStart, $morningEnd) || $currentTime->between($eveningStart, $eveningEnd));
                            })->first();

                            // عدد الحاضرين في الشفت الحالي
                            $attendanceCount = $currentShift
                                ? $currentShift->attendances->where('status', 'present')->count()
                                : 0;

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'current_shift' => $currentShift ? [
                                    'id' => $currentShift->id,
                                    'name' => $currentShift->name,
                                    'type' => $currentShift->morning_start ? 'morning' : 'evening',
                                    'attendees_count' => $attendanceCount,
                                ] : null,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }
    public function getAreasWithDetails2()
    {
        // تعيين التوقيت إلى توقيت الرياض
        $currentTime = Carbon::now('Asia/Riyadh');
    
        $areas = Area::with(['projects.zones.shifts.attendances'])->get();
    
        $data = $areas->map(function ($area) use ($currentTime) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($currentTime) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'emp_no' => $project->emp_no,
                        'zones' => $project->zones->map(function ($zone) use ($currentTime) {
                            $shifts = $zone->shifts->map(function ($shift) use ($currentTime, $zone) {
                                $isCurrentShift = $this->isCurrentShift($shift, $currentTime, $zone);
    
                                // تعداد الحضور في الشفت الحالي لهذا اليوم
                                $attendanceCount = $shift->attendances
                                    ->where('status', 'present')
                                    ->where('date', Carbon::today('Asia/Riyadh')->toDateString())
                                    ->count();
    
                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->morning_start ? 'morning' : 'evening',
                                    'is_current_shift' => $isCurrentShift,
                                    'attendees_count' => $attendanceCount,
                                    'emp_no' => $shift->emp_no,
                                ];
                            });
    
                            $currentShift = $shifts->where('is_current_shift', true)->first();
                                // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا
                                $activeCoveragesCount = $zone->shifts
                                ->flatMap(fn($shift) => $shift->attendances)
                                ->where('status', 'coverage')
                                ->where('check_out', null)
                                ->count();
                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $shifts,
                                'current_shift_emp_no' => $currentShift ? $currentShift['emp_no'] : 0,
                                'active_coverages_count' => $activeCoveragesCount, // عدد التغطيات النشطة

                            ];
                        }),
                    ];
                }),
            ];
        });
    
        return response()->json($data);
    }
    
    private function isCurrentShift($shift, $currentTime, $zone)
    {
        // تحقق من إذا كان اليوم يوم عمل
        $isWorkingDay = $shift->isWorkingDay();
    
        // تحليل النوع وتحديد الوردية الحالية
        $morningStart = Carbon::createFromTimeString($shift->morning_start, 'Asia/Riyadh');
        $morningEnd = Carbon::createFromTimeString($shift->morning_end, 'Asia/Riyadh');
        $eveningStart = Carbon::createFromTimeString($shift->evening_start, 'Asia/Riyadh');
        $eveningEnd = Carbon::createFromTimeString($shift->evening_end, 'Asia/Riyadh');
    
        // التحقق من امتداد الفترة عبر منتصف الليل
if ($eveningEnd->lessThan($eveningStart)) {
    $eveningEnd = $eveningEnd->addDay(); // إضافة يوم إلى وقت النهاية
}
        $isWithinShiftTime = false;
    
        switch ($shift->type) {
            case 'morning':
                $isWithinShiftTime = $currentTime->between($morningStart, $morningEnd);
                break;
    
            case 'evening':
                $isWithinShiftTime = $currentTime->between($eveningStart, $eveningEnd);
                break;
    
            case 'morning_evening':
                $isWithinShiftTime = $this->determineShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, 'morning_evening');
                break;
    
            case 'evening_morning':
                $isWithinShiftTime = $this->determineShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, 'evening_morning');
                break;
        }
    
        // الشرط النهائي
        return $isWorkingDay && $isWithinShiftTime;
    }


    private function determineShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, $type)
{
    // دورة العمل = عدد أيام العمل + الإجازة
   
    if (!$shift->zone || !$shift->zone->pattern) {
        // إذا لم تكن هناك بيانات كافية
        return false;
    }

    $pattern = $shift->zone->pattern;

    $cycleLength = $pattern->working_days + $pattern->off_days;

    // تحقق إذا كانت دورة العمل غير صالحة (صفر أو أقل)
if ($cycleLength <= 0) {

    throw new Exception('Cycle length must be greater than zero. Please check the working_days and off_days values.');
}

    // تاريخ بداية الوردية
    $startDate = Carbon::parse($shift->start_date)->startOfDay();

    // عدد الأيام منذ تاريخ البداية
    $daysSinceStart = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));

    // رقم الدورة الحالية
    $currentCycleNumber = floor($daysSinceStart / $cycleLength) + 1;

    // اليوم الحالي داخل الدورة
    $currentDayInCycle = $daysSinceStart % $cycleLength;

    // إذا كان اليوم الحالي داخل أيام العمل
    $isWorkingDayInCycle = $currentDayInCycle < $pattern->working_days;

    // تحديد إذا كانت الدورة الحالية فردية أو زوجية
    $isOddCycle = $currentCycleNumber % 2 === 0;

    // تحديد الوردية الحالية بناءً على نوعها
    if ($type === 'morning_evening') {
        // دورة فردية: صباحية، دورة زوجية: مسائية
        return $isWorkingDayInCycle && (
            ($isOddCycle && $currentTime->between($morningStart, $morningEnd)) ||
            (!$isOddCycle && $currentTime->between($eveningStart, $eveningEnd))
        );
    }

    if ($type === 'evening_morning') {
        // دورة فردية: مسائية، دورة زوجية: صباحية
        return $isWorkingDayInCycle && (
            ($isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) ||
            (!$isOddCycle && $currentTime->between($morningStart, $morningEnd))
        );
    }

    return false; // الأنواع الأخرى ليست متداخلة
}

    
    

    }
