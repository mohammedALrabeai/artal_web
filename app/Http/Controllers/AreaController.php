<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Carbon\Carbon;
use Exception;

class AreaController extends Controller
{
    public function getAssignedEmployeesForShifts()
    {
        // تعيين التوقيت إلى توقيت الرياض
        $currentTime = Carbon::now('Asia/Riyadh');

        $areas = Area::with(['projects.zones.shifts'])->get();

        $data = $areas->map(function ($area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) { // ← نمرر $project هنا
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'emp_no' => $project->emp_no,
                        'zones' => $project->zones->map(function ($zone) use ($project) { // ← نمرر $project هنا
                            $shifts = $zone->shifts->map(function ($shift) use ($zone, $project) { // ← ثم نمرر $project هنا أيضًا
                                // حساب عدد الموظفين المسندين إلى هذه الوردية بناءً على السجلات في EmployeeProjectRecord
                                $assignedEmployeesCount = \App\Models\EmployeeProjectRecord::where('shift_id', $shift->id)
                                    ->where('zone_id', $zone->id)
                                    ->where('project_id', $project->id)
                                    ->where('status', 1) // ✅ تعديل هذا الجزء ليبحث عن 1 بدلاً من 'active'
                                    ->count();

                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->morning_start ? 'morning' : 'evening',
                                    'assigned_employees_count' => $assignedEmployeesCount,
                                    'emp_no' => $shift->emp_no,
                                ];
                            });

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $shifts,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }

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

                                return $currentTime->between($morningStart, $morningEnd) || $currentTime->between($eveningStart, $eveningEnd);
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
                                // $today = $currentTime->toDateString();

                                // // // إنشاء أوقات الوردية مع التاريخ
                                // $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
                                // $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');
                                // $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
                                // $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');
                                // if ($eveningEnd->lessThan($eveningStart)) {
                                //     $eveningEnd->addDay();
                                // }

                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    // 'isWorkingDay' => $shift->isWorkingDay(),
                                    // 'current_time' => $currentTime->toTimeString(),
                                    // 'morning_start2' => $shift->morning_start,
                                    // 'morning_start' => $morningStart,
                                    // 'morning_end' => $morningEnd,
                                    // 'ism' => $currentTime->between($morningStart, $morningEnd),
                                    // 'evening_start' => $eveningStart,
                                    // 'evening_end' => $eveningEnd,
                                    // 'ise' => $currentTime->between($eveningStart, $eveningEnd),
                                    // 'type' => $shift->morning_start ? 'morning' : 'evening',
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrentShift,
                                    'attendees_count' => $attendanceCount,
                                    'emp_no' => $shift->emp_no,
                                ];
                            });

                            $currentShift = $shifts->where('is_current_shift', true)->first();
                            // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا في اليوم الحالي
                            $activeCoveragesCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'coverage')
                                ->where('check_out', null)
                                ->whereDate('date', $currentTime->toDateString())
                                ->count();

                            // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا وخرجوا عن الموقع
                            $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'present') // الحضور
                                ->where('check_out', null) // بدون انصراف
                                ->whereDate('date', $currentTime->toDateString())
                                ->whereHas('employee', function ($query) {
                                    $query->where('out_of_zone', true); // التحقق من حالة الموظف خارج النطاق
                                })
                                ->count();

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $shifts,
                                'current_shift_emp_no' => $currentShift ? $currentShift['emp_no'] : 0,
                                'active_coverages_count' => $activeCoveragesCount, // عدد التغطيات النشطة
                                'out_of_zone_count' => $outOfZoneCount,
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

        // نحصل على تاريخ اليوم من $currentTime
        $today = $currentTime->toDateString();

        // $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
        // $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');
        // $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
        // $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');
        // if ($eveningEnd->lessThan($eveningStart)) {
        //     $eveningEnd->addDay();
        // }

        // إنشاء أوقات الوردية مع التاريخ
        $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
        $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');
        $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
        $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');

        // التحقق من الفترة التي تمتد عبر منتصف الليل وإضافة يوم لنهاية الفترة إذا لزم الأمر
        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
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

        if (! $shift->zone || ! $shift->zone->pattern) {
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
        // $startDate = Carbon::parse($shift->start_date)->startOfDay();
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));

        // رقم الدورة الحالية
        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;

        // اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // إذا كان اليوم الحالي داخل أيام العمل
        $isWorkingDayInCycle = $currentDayInCycle < $pattern->working_days;

        // تحديد إذا كانت الدورة الحالية فردية أو زوجية
        $isOddCycle = $currentCycleNumber % 2 == 1;
        // if ($shift->name == 'الوردية الاولى A' && $shift->zone->name == 'موقع شركة ENPPI الجعيمة') {
        //     \Log::info('isOddCycle', ['isOddCycle' => $isOddCycle, 'currentCycleNumber' => $currentCycleNumber]);
        // }

        // تحديد الوردية الحالية بناءً على نوعها
        if ($type === 'morning_evening') {
            // دورة فردية: صباحية، دورة زوجية: مسائية
            return $isWorkingDayInCycle && (
                ($isOddCycle && $currentTime->between($morningStart, $morningEnd)) ||
                ((! $isOddCycle) && $currentTime->between($eveningStart, $eveningEnd))
            );
        }

        if ($type === 'evening_morning') {
            // دورة فردية: مسائية، دورة زوجية: صباحية
            return $isWorkingDayInCycle && (
                ($isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) ||
                ((! $isOddCycle) && $currentTime->between($morningStart, $morningEnd))
            );
        }

        return false; // الأنواع الأخرى ليست متداخلة
    }

    public function getAreasWithDetails3()
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
                                $shiftStatus = $this->isCurrentShift3($shift, $currentTime, $zone);
                                $isCurrentShift = $shiftStatus['is_current'];
                                $shiftStartDate = $shiftStatus['start_date'];

                                $attendanceCount = 0;

                                if ($isCurrentShift && $shiftStartDate) {
                                    $attendanceCount = $shift->attendances
                                        ->where('status', 'present')
                                        ->where('date', $shiftStartDate)
                                        ->count();
                                }
                                // $today = $currentTime->toDateString();

                                // // // إنشاء أوقات الوردية مع التاريخ
                                // $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
                                // $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');
                                // $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
                                // $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');
                                // if ($eveningEnd->lessThan($eveningStart)) {
                                //     $eveningEnd->addDay();
                                // }

                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    // 'isWorkingDay' => $shift->isWorkingDay(),
                                    // 'current_time' => $currentTime->toTimeString(),
                                    // 'morning_start2' => $shift->morning_start,
                                    // 'morning_start' => $morningStart,
                                    // 'morning_end' => $morningEnd,
                                    // 'ism' => $currentTime->between($morningStart, $morningEnd),
                                    // 'evening_start' => $eveningStart,
                                    // 'evening_end' => $eveningEnd,
                                    // 'ise' => $currentTime->between($eveningStart, $eveningEnd),
                                    // 'type' => $shift->morning_start ? 'morning' : 'evening',
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrentShift,
                                    'attendees_count' => $attendanceCount,
                                    'emp_no' => $shift->emp_no,
                                ];
                            });

                            $currentShift = $shifts->where('is_current_shift', true)->first();
                            // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا في اليوم الحالي
                            $activeCoveragesCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'coverage')
                                ->where('check_out', null)
                                ->whereDate('date', $currentTime->toDateString())
                                ->count();

                            // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا وخرجوا عن الموقع
                            $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'present') // الحضور
                                ->where('check_out', null) // بدون انصراف
                                ->whereDate('date', $currentTime->toDateString())
                                ->whereHas('employee', function ($query) {
                                    $query->where('out_of_zone', true); // التحقق من حالة الموظف خارج النطاق
                                })
                                ->count();

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $shifts,
                                'current_shift_emp_no' => $currentShift ? $currentShift['emp_no'] : 0,
                                'active_coverages_count' => $activeCoveragesCount, // عدد التغطيات النشطة
                                'out_of_zone_count' => $outOfZoneCount,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }

    private function isCurrentShift3($shift, $currentTime, $zone): array
    {
        $isWorkingDay = $shift->isWorkingDay2($currentTime);

        // أوقات الصباح
        $morningStart = Carbon::parse($shift->morning_start, 'Asia/Riyadh')->setDateFrom($currentTime);
        $morningEnd = Carbon::parse($shift->morning_end, 'Asia/Riyadh')->setDateFrom($currentTime);
        if ($morningEnd->lessThan($morningStart)) {
            $morningEnd->addDay();
        }

        // أوقات المساء
        if ($currentTime->hour < 6) {
            $eveningStart = Carbon::parse($shift->evening_start, 'Asia/Riyadh')->setDateFrom($currentTime)->subDay();
            $eveningEnd = Carbon::parse($shift->evening_end, 'Asia/Riyadh')->setDateFrom($currentTime);
        } else {
            $eveningStart = Carbon::parse($shift->evening_start, 'Asia/Riyadh')->setDateFrom($currentTime);
            $eveningEnd = Carbon::parse($shift->evening_end, 'Asia/Riyadh')->setDateFrom($currentTime);
        }
        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
        }

        $isWithinShiftTime = false;
        $shiftStartDate = null;

        switch ($shift->type) {
            case 'morning':
                $isWithinShiftTime = $currentTime->between($morningStart, $morningEnd);
                if ($isWithinShiftTime) {
                    $shiftStartDate = $morningStart->toDateString();
                }
                break;

            case 'evening':
                $isWithinShiftTime = $currentTime->between($eveningStart, $eveningEnd);
                if ($isWithinShiftTime) {
                    $shiftStartDate = $eveningStart->toDateString();
                }
                break;

            case 'morning_evening':
            case 'evening_morning':
                $isWithinShiftTime = $this->determineShiftCycle2(
                    $shift,
                    $currentTime,
                    $morningStart,
                    $morningEnd,
                    $eveningStart,
                    $eveningEnd,
                    $shift->type
                );
                if ($isWithinShiftTime) {
                    $shiftStartDate = $eveningStart->toDateString(); // أو morningStart حسب نوع الدورة
                }
                break;
        }

        return [
            'is_current' => $isWorkingDay && $isWithinShiftTime,
            'start_date' => $shiftStartDate,
        ];
    }

    private function determineShiftCycle2($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, $type)
    {
        $pattern = $shift->zone->pattern;
        $workingDays = $pattern->working_days;
        $offDays = $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        // تاريخ بداية الدورة مع وقت الوردية
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh');

        // تعديل بداية الدورة لورديات المساء
        if ($shift->type === 'evening' || $shift->type === 'evening_morning') {
            $startDate->subHours(4);
        }

        // عدد الأيام منذ بداية الدورة (مع الوقت)
        $daysSinceStart = $startDate->diffInDays($currentTime, false);

        if ($daysSinceStart < 0) {
            return ['is_current' => false, 'start_date' => null];
        }

        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        $isWorkingDayInCycle = $currentDayInCycle < $workingDays;
        $isOddCycle = $currentCycleNumber % 2 == 1;

        $isCurrent = false;
        $shiftStartDate = null;

        if ($type === 'morning_evening') {
            if ($isOddCycle && $currentTime->between($morningStart, $morningEnd)) {
                $isCurrent = true;
                $shiftStartDate = $morningStart->toDateString();
            } elseif (! $isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) {
                $isCurrent = true;
                $shiftStartDate = $eveningStart->toDateString();
            }
        } elseif ($type === 'evening_morning') {
            if ($isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) {
                $isCurrent = true;
                $shiftStartDate = $eveningStart->toDateString();
            } elseif (! $isOddCycle && $currentTime->between($morningStart, $morningEnd)) {
                $isCurrent = true;
                $shiftStartDate = $morningStart->toDateString();
            }
        }

        return [
            'is_current' => $isWorkingDayInCycle && $isCurrent,
            'start_date' => $shiftStartDate,
        ];
    }

    public function getAreasWithDetailsDynamic()
    {
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
                                $isCurrentShift = $this->isCurrentShiftDynamic($shift, $currentTime, $zone);

                                // تحديد تاريخ الحضور بناءً على الوردية
                                $attendanceDate = $this->getAttendanceDate($shift, $currentTime);

                                // احتساب عدد الحضور للوردية الحالية فقط
                                $attendanceCount = $isCurrentShift ? $shift->attendances
                                    ->where('status', 'present')
                                    ->where('date', $attendanceDate)
                                    ->count() : 0;

                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrentShift,
                                    'attendees_count' => $attendanceCount,
                                    'emp_no' => $shift->emp_no,
                                ];
                            });

                            $currentShift = $shifts->where('is_current_shift', true)->first();
                            $activeCoveragesCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'coverage')
                                ->where('check_out', null)
                                ->whereDate('date', $currentTime->toDateString())
                                ->count();

                            $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'present')
                                ->where('check_out', null)
                                ->whereDate('date', $currentTime->toDateString())
                                ->whereHas('employee', function ($query) {
                                    $query->where('out_of_zone', true);
                                })
                                ->count();

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $shifts,
                                'current_shift_emp_no' => $currentShift ? $currentShift['emp_no'] : 0,
                                'active_coverages_count' => $activeCoveragesCount,
                                'out_of_zone_count' => $outOfZoneCount,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }

    private function isCurrentShiftDynamic($shift, $currentTime, $zone)
    {
        $isWorkingDay = $shift->isWorkingDayDynamic(Carbon::now('Asia/Riyadh'));

        $morningStart = Carbon::parse($shift->morning_start, 'Asia/Riyadh');
        $morningEnd = Carbon::parse($shift->morning_end, 'Asia/Riyadh');
        $eveningStart = Carbon::parse($shift->evening_start, 'Asia/Riyadh');
        $eveningEnd = Carbon::parse($shift->evening_end, 'Asia/Riyadh');

        // تعديل نهاية الوردية إذا كانت تمتد عبر منتصف الليل
        if ($eveningEnd < $eveningStart) {
            $eveningEnd->addDay();
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
            case 'evening_morning':
                $isWithinShiftTime = $this->determineShiftCycleDynamic($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, $shift->type);
                break;
        }

        return $isWorkingDay && $isWithinShiftTime;
    }

    private function determineShiftCycleDynamic($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, $type)
    {
        if (! $shift->zone || ! $shift->zone->pattern) {
            return false;
        }

        $pattern = $shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            throw new Exception('Cycle length must be greater than zero.');
        }

        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();
        $daysSinceStart = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));
        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;
        $currentDayInCycle = $daysSinceStart % $cycleLength;
        $isWorkingDayInCycle = $currentDayInCycle < $pattern->working_days;
        $isOddCycle = $currentCycleNumber % 2 == 1;

        if ($type === 'morning_evening') {
            return $isWorkingDayInCycle && (
                ($isOddCycle && $currentTime->between($morningStart, $morningEnd)) ||
                ((! $isOddCycle) && $currentTime->between($eveningStart, $eveningEnd))
            );
        }

        if ($type === 'evening_morning') {
            return $isWorkingDayInCycle && (
                ($isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) ||
                ((! $isOddCycle) && $currentTime->between($morningStart, $morningEnd))
            );
        }

        return false;
    }

    private function getAttendanceDate($shift, $currentTime)
    {
        $eveningStart = Carbon::parse($shift->evening_start, 'Asia/Riyadh');
        $eveningEnd = Carbon::parse($shift->evening_end, 'Asia/Riyadh');

        if ($eveningEnd < $eveningStart) {
            if ($currentTime->hour < 12) {
                return Carbon::yesterday('Asia/Riyadh')->toDateString();
            }
        }

        return Carbon::today('Asia/Riyadh')->toDateString();
    }
}
