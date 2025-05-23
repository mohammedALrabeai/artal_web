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

        $areas = Area::with(['activeProjects.activeZones.shifts.attendances'])->get();

        $data = $areas->map(function ($area) use ($currentTime) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->activeProjects

                    ->map(function ($project) use ($currentTime) {
                        return [
                            'id' => $project->id,
                            'name' => $project->name,
                            'emp_no' => $project->emp_no,
                            'zones' => $project->activeZones->map(function ($zone) use ($currentTime) {
                                $shifts = $zone->shifts->map(function ($shift) use ($currentTime, $zone) {
                                    // الحصول على حالة الوردية وتاريخ الحضور
                                    $shiftInfo = $this->determineCurrentShift($shift, $currentTime, $zone);

                                    // تعداد الحضور بناءً على تاريخ الوردية
                                    $attendanceCount = $shift->attendances
                                        ->where('status', 'present')
                                        ->where('date', $shiftInfo['attendance_date'] ?? null)
                                        ->whereNull('check_out')
                                        ->count();

                                    return [
                                        'id' => $shift->id,
                                        'name' => $shift->name,
                                        'type' => $shift->type,
                                        'is_current_shift' => $shiftInfo['is_current'],
                                        'attendees_count' => $attendanceCount,
                                        'emp_no' => $shift->emp_no,
                                    ];
                                });

                                $currentShift = $shifts->where('is_current_shift', true)->first();

                                $activeCoveragesCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                    ->where('status', 'coverage')
                                    ->whereNull('check_out')
                                    ->whereDate('date', $currentTime->toDateString())
                                    ->count();

                                $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                    ->where('status', 'present')
                                    ->whereNull('check_out')
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

    /**
     * تحديد إذا كانت الوردية حالية مع تاريخ الحضور المناسب
     */
    private function determineCurrentShift($shift, $currentTime, $zone)
    {
        $isWorkingDay = $shift->isWorkingDay();
        $today = $currentTime->toDateString();
        $attendanceDate = $today;
        $isCurrent = false;

        // تحليل أوقات الورديات
        $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
        $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');

        $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
        $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');

        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
        }

        switch ($shift->type) {
            case 'morning':
                $isCurrent = $isWorkingDay && $currentTime->between($morningStart, $morningEnd);
                break;

            case 'evening':
                // التحقق من الوردية اليوم أو الأمس
                $yesterday = $currentTime->copy()->subDay()->toDateString();
                $yesterdayEveningStart = Carbon::parse("$yesterday {$shift->evening_start}", 'Asia/Riyadh');
                $yesterdayEveningEnd = Carbon::parse("$yesterday {$shift->evening_end}", 'Asia/Riyadh');

                if ($yesterdayEveningEnd->lessThan($yesterdayEveningStart)) {
                    $yesterdayEveningEnd->addDay();
                }

                $isCurrent = $isWorkingDay && (
                    $currentTime->between($eveningStart, $eveningEnd) ||
                    $currentTime->between($yesterdayEveningStart, $yesterdayEveningEnd)
                );

                // تحديد تاريخ الحضور
                if ($currentTime->between($yesterdayEveningStart, $yesterdayEveningEnd)) {
                    $attendanceDate = $yesterday;
                }
                break;

            case 'morning_evening':
            case 'evening_morning':
                $cycleCheck = $this->checkShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd);
                $isCurrent = $isWorkingDay && $cycleCheck['is_current'];
                $attendanceDate = $cycleCheck['attendance_date'];
                break;
        }

        return [
            'is_current' => $isCurrent,
            'attendance_date' => $attendanceDate,
        ];
    }

    /**
     * التحقق من دورة العمل المعقدة (صباحية/مسائية)
     */
    private function checkShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd)
    {
        if (! $shift->zone || ! $shift->zone->pattern) {
            return ['is_current' => false, 'attendance_date' => null];
        }

        $pattern = $shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            return ['is_current' => false, 'attendance_date' => null];
        }

        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh');
        $daysSinceStart = $startDate->diffInDays($currentTime);
        $currentCycle = (int) floor($daysSinceStart / $cycleLength) + 1;
        $currentDayInCycle = $daysSinceStart % $cycleLength;
        $isWorkingDay = $currentDayInCycle < $pattern->working_days;
        $isOddCycle = $currentCycle % 2 == 1;

        $attendanceDate = $currentTime->toDateString();
        $isCurrent = false;

        // تحديد نوع الوردية بناءً على الدورة
        if ($shift->type === 'morning_evening') {
            if ($isOddCycle) {
                $isCurrent = $currentTime->between($morningStart, $morningEnd);
            } else {
                // التحقق من الوردية المسائية (اليوم أو الأمس)
                $yesterday = $currentTime->copy()->subDay()->toDateString();
                $yesterdayEveningStart = Carbon::parse("$yesterday {$shift->evening_start}", 'Asia/Riyadh');
                $yesterdayEveningEnd = Carbon::parse("$yesterday {$shift->evening_end}", 'Asia/Riyadh');

                if ($yesterdayEveningEnd->lessThan($yesterdayEveningStart)) {
                    $yesterdayEveningEnd->addDay();
                }

                $isCurrent = $currentTime->between($yesterdayEveningStart, $yesterdayEveningEnd);
                if ($isCurrent) {
                    $attendanceDate = $yesterday;
                }
            }
        } elseif ($shift->type === 'evening_morning') {
            if ($isOddCycle) {
                // التحقق من الوردية المسائية (اليوم أو الأمس)
                $yesterday = $currentTime->copy()->subDay()->toDateString();
                $yesterdayEveningStart = Carbon::parse("$yesterday {$shift->evening_start}", 'Asia/Riyadh');
                $yesterdayEveningEnd = Carbon::parse("$yesterday {$shift->evening_end}", 'Asia/Riyadh');

                if ($yesterdayEveningEnd->lessThan($yesterdayEveningStart)) {
                    $yesterdayEveningEnd->addDay();
                }

                $isCurrent = $currentTime->between($yesterdayEveningStart, $yesterdayEveningEnd);
                if ($isCurrent) {
                    $attendanceDate = $yesterday;
                }
            } else {
                $isCurrent = $currentTime->between($morningStart, $morningEnd);
            }
        }

        return [
            'is_current' => $isWorkingDay && $isCurrent,
            'attendance_date' => $attendanceDate,
        ];
    }

    /**
     * دالة محسنة لعرض الورديات في شاشة المراقبة
     * تعالج مشكلة الورديات التي تمتد عبر منتصف الليل وأنماط العمل المختلفة
     */
    public function getAreasWithDetailsImproved()
    {
        // تعيين التوقيت إلى توقيت الرياض
        $currentTime = Carbon::now('Asia/Riyadh');
        $today = $currentTime->toDateString();
        $yesterday = $currentTime->copy()->subDay()->toDateString();

        $areas = Area::with(['projects.zones.shifts.attendances'])->get();

        $data = $areas->map(function ($area) use ($currentTime, $today, $yesterday) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($currentTime, $today, $yesterday) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'emp_no' => $project->emp_no,
                        'zones' => $project->zones->map(function ($zone) use ($currentTime, $today, $yesterday) {
                            // الحصول على جميع الورديات النشطة حالياً (من اليوم الحالي واليوم السابق)
                            $activeShifts = $this->getActiveShifts($zone->shifts, $currentTime, $zone);

                            $shifts = $zone->shifts->map(function ($shift) use ($activeShifts) {
                                // التحقق مما إذا كانت الوردية نشطة حالياً
                                $shiftInfo = $activeShifts->firstWhere('shift_id', $shift->id);
                                $isCurrentShift = ! is_null($shiftInfo);

                                // تحديد تاريخ الحضور المناسب للوردية
                                $attendanceDate = $isCurrentShift ? $shiftInfo['attendance_date'] : null;

                                // تعداد الحضور في الوردية الحالية
                                $attendanceCount = 0;
                                if ($isCurrentShift) {
                                    $attendanceCount = $shift->attendances
                                        ->where('status', 'present')
                                        ->where('date', $attendanceDate)
                                        ->whereNull('check_out')
                                        ->count();
                                }

                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrentShift,
                                    'attendees_count' => $attendanceCount,
                                    'emp_no' => $shift->emp_no,
                                    'attendance_date' => $attendanceDate,
                                    'shift_cycle_info' => $isCurrentShift ? $shiftInfo['cycle_info'] : null,
                                ];
                            });
                            $timezone = 'Asia/Riyadh';
                            $nowInRiyadh = Carbon::now($timezone);
                            // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا في اليوم الحالي أو السابق
                            $activeCoveragesCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'coverage')
                                ->whereNull('check_out')
                                ->whereIn('date', [$today, $yesterday])
                                ->where('check_in', '>=', $nowInRiyadh->subHours(16)->timezone('UTC')) // فقط التغطيات التي مضى عليها أقل من 12 ساعة
                                ->count();

                            // عدد الموظفين الذين قاموا بتغطية ولم يسجلوا انصرافًا وخرجوا عن الموقع
                            $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'present')
                                ->whereNull('check_out')
                                ->whereIn('date', [$today, $yesterday])
                                ->whereHas('employee', function ($query) {
                                    $query->where('out_of_zone', true);
                                })
                                ->count();

                            // الحصول على الوردية الحالية لعرض عدد الموظفين المطلوبين
                            $currentShift = $shifts->where('is_current_shift', true)->first();

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

    /**
     * الحصول على جميع الورديات النشطة حالياً
     * تعالج الورديات من اليوم الحالي واليوم السابق
     */
    private function getActiveShifts($shifts, $currentTime, $zone)
    {
        $today = $currentTime->toDateString();
        $yesterday = $currentTime->copy()->subDay()->toDateString();
        $activeShifts = collect();

        foreach ($shifts as $shift) {
            // التحقق من الورديات النشطة من اليوم الحالي
            $todayShiftInfo = $this->checkShiftActive($shift, $currentTime, $zone, $today);
            if ($todayShiftInfo['is_active']) {
                $activeShifts->push([
                    'shift_id' => $shift->id,
                    'attendance_date' => $todayShiftInfo['attendance_date'],
                    'cycle_info' => $todayShiftInfo['cycle_info'],
                ]);

                continue; // إذا كانت الوردية نشطة اليوم، لا داعي للتحقق من الأمس
            }

            // التحقق من الورديات النشطة من اليوم السابق
            $yesterdayShiftInfo = $this->checkShiftActive($shift, $currentTime, $zone, $yesterday);
            if ($yesterdayShiftInfo['is_active']) {
                $activeShifts->push([
                    'shift_id' => $shift->id,
                    'attendance_date' => $yesterdayShiftInfo['attendance_date'],
                    'cycle_info' => $yesterdayShiftInfo['cycle_info'],
                ]);
            }
        }

        return $activeShifts;
    }

    /**
     * التحقق مما إذا كانت الوردية نشطة في تاريخ محدد
     */
    private function checkShiftActive($shift, $currentTime, $zone, $checkDate)
    {
        // التحقق من إذا كان اليوم يوم عمل بناءً على نمط العمل
        $isWorkingDay = $this->isWorkingDayInPattern($shift, $checkDate);
        if (! $isWorkingDay) {
            return ['is_active' => false, 'attendance_date' => null, 'cycle_info' => null];
        }

        // إنشاء أوقات الوردية مع التاريخ المحدد
        $morningStart = Carbon::parse("$checkDate {$shift->morning_start}", 'Asia/Riyadh');
        $morningEnd = Carbon::parse("$checkDate {$shift->morning_end}", 'Asia/Riyadh');
        $eveningStart = Carbon::parse("$checkDate {$shift->evening_start}", 'Asia/Riyadh');
        $eveningEnd = Carbon::parse("$checkDate {$shift->evening_end}", 'Asia/Riyadh');

        // التعامل مع الورديات التي تمتد عبر منتصف الليل
        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
        }

        // الحصول على معلومات دورة العمل
        $cycleInfo = $this->getShiftCycleInfo($shift, $checkDate);
        $isOddCycle = $cycleInfo['is_odd_cycle'];
        $currentCycleNumber = $cycleInfo['current_cycle_number'];
        $currentDayInCycle = $cycleInfo['current_day_in_cycle'];

        $isActive = false;
        $attendanceDate = $checkDate;

        switch ($shift->type) {
            case 'morning':
                $isActive = $currentTime->between($morningStart, $morningEnd);
                break;

            case 'evening':
                $isActive = $currentTime->between($eveningStart, $eveningEnd);
                break;

            case 'morning_evening':
                // دورة فردية: صباحية، دورة زوجية: مسائية
                if ($isOddCycle) {
                    $isActive = $currentTime->between($morningStart, $morningEnd);
                } else {
                    $isActive = $currentTime->between($eveningStart, $eveningEnd);
                }
                break;

            case 'evening_morning':
                // دورة فردية: مسائية، دورة زوجية: صباحية
                if ($isOddCycle) {
                    $isActive = $currentTime->between($eveningStart, $eveningEnd);
                } else {
                    $isActive = $currentTime->between($morningStart, $morningEnd);
                }
                break;
        }

        return [
            'is_active' => $isActive,
            'attendance_date' => $isActive ? $attendanceDate : null,
            'cycle_info' => [
                'is_odd_cycle' => $isOddCycle,
                'current_cycle_number' => $currentCycleNumber,
                'current_day_in_cycle' => $currentDayInCycle,
            ],
        ];
    }

    /**
     * التحقق مما إذا كان اليوم يوم عمل بناءً على نمط العمل
     */
    private function isWorkingDayInPattern($shift, $checkDate)
    {
        if (! $shift->zone || ! $shift->zone->pattern) {
            return false;
        }

        $pattern = $shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            return false;
        }

        // تاريخ بداية الوردية
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();
        $checkDateObj = Carbon::parse($checkDate, 'Asia/Riyadh')->startOfDay();

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays($checkDateObj);

        // اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // إذا كان اليوم الحالي داخل أيام العمل
        return $currentDayInCycle < $pattern->working_days;
    }

    /**
     * الحصول على معلومات دورة العمل للوردية
     */
    private function getShiftCycleInfo($shift, $checkDate)
    {
        if (! $shift->zone || ! $shift->zone->pattern) {
            return [
                'is_odd_cycle' => false,
                'current_cycle_number' => 0,
                'current_day_in_cycle' => 0,
            ];
        }

        $pattern = $shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            return [
                'is_odd_cycle' => false,
                'current_cycle_number' => 0,
                'current_day_in_cycle' => 0,
            ];
        }

        // تاريخ بداية الوردية
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();
        $checkDateObj = Carbon::parse($checkDate, 'Asia/Riyadh')->startOfDay();

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays($checkDateObj);

        // رقم الدورة الحالية
        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;

        // اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // تحديد إذا كانت الدورة الحالية فردية أو زوجية
        $isOddCycle = $currentCycleNumber % 2 == 1;

        return [
            'is_odd_cycle' => $isOddCycle,
            'current_cycle_number' => $currentCycleNumber,
            'current_day_in_cycle' => $currentDayInCycle,
        ];
    }
}
