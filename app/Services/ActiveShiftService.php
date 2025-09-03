<?php

namespace App\Services;

use App\Models\Area;
use Carbon\Carbon;

class ActiveShiftService
{
    public function getActiveShiftsSummaryV3(?Carbon $now = null): array
{
    $now = $now ? $now->copy()->tz('Asia/Riyadh') : Carbon::now('Asia/Riyadh');

    $intervalSeconds = 60; // ← 🟡 يمكنك تغييرها إلى 60 أو 120 أو 15 حسب ما تريد

    $cacheKey = 'active_shifts_summary';

    return cache()->remember($cacheKey, now()->addSeconds($intervalSeconds), function () use ($now) {
        logger('🚀 تنفيذ فعلي للدالة getActiveShiftsSummaryV2');
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();

        // بصمة[1]: إزالة coveredToday / coveredYesterday لأننا سنستخدم نافذة ديناميكية بدل whereDate/Between
        // $rangeToday = [$today, $now->copy()->endOfDay()];
        // $rangeYesterday = [$yesterday, $now->copy()->endOfDay()];
        // $coveredToday = \App\Models\Attendance::query()
        //     ->where('is_coverage', true)
        //     ->whereBetween('created_at', $rangeToday)
        //     ->pluck('employee_id')
        //     ->toArray();
        // $coveredYesterday = \App\Models\Attendance::query()
        //     ->where('is_coverage', true)
        //     ->whereBetween('created_at', $rangeYesterday)
        //     ->pluck('employee_id')
        //     ->toArray();

        $missingMap = [];
        $summary = Area::with([
            'projects.zones.shifts' => function ($q) {
                $q->where('status', 1); // فقط الورديات النشطة
            },
            'projects.zones' => function ($q) {
                $q->where('status', 1); // فقط المواقع النشطة
            },
            'projects' => function ($q) {
                $q->where('status', 1); // فقط المشاريع النشطة
            }
        ])->get()->map(function ($area) use (&$missingMap, $now) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use (&$missingMap, $now) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'emp_no' => $project->emp_no,
                        'zones' => $project->zones->map(function ($zone) use (&$missingMap, $now) {

                            $activeShifts = [];
                            $currentShiftEmpNo = 0;
                            $allCurrentShiftsAttendeesCount = 0;

                            // بصمة[2]: Lookback ساعات للتغطيات عندما توجد وردية حالية بدأت أمس
                            $lookbackHours = 13;

                            // بصمة[3]: هل توجد في هذا الـ Zone وردية "حالية" بدأت أمس؟
                            $hasYesterdayShift = false;
                            foreach ($zone->shifts as $s0) {
                                [$isCur0, $startedAt0] = $s0->getShiftActiveStatus2($now);
                                if ($isCur0 && $startedAt0 === 'yesterday') {
                                    $hasYesterdayShift = true;
                                    break;
                                }
                            }

                            // بصمة[4]: نافذة التغطيات على مستوى الـ Zone
                            // - لو فيه وردية بدأت أمس → اجعل بداية النافذة هي الأقدم بين (بداية اليوم) و(الآن - lookbackHours)
                            // - غير ذلك → بداية اليوم فقط
                            $coverageWindowStartZone = $now->copy()->startOfDay();
                            if ($hasYesterdayShift) {
                                $cutoff = $now->copy()->subHours($lookbackHours);
                                if ($cutoff->lt($coverageWindowStartZone)) {
                                    $coverageWindowStartZone = $cutoff;
                                }
                            }

                            foreach ($zone->shifts as $shift) {
                                [$isCurrent, $startedAt] = $shift->getShiftActiveStatus2($now); // ← ✅

                                $attendanceDateRange = match ($startedAt) {
                                    'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                                    'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->endOfDay()],
                                    default => [null, null], // غير نشط
                                };

                                $attendeesCount = 0;
                                if ($attendanceDateRange[0] && $attendanceDateRange[1]) {
                                    $attendeesCount = $shift->attendances()
                                        ->whereBetween('created_at', $attendanceDateRange)
                                        ->where('shift_id', $shift->id)
                                        ->where('zone_id', $zone->id)
                                        ->where('status', 'present')
                                        ->whereNull('check_out')   // ← حاضر "الآن"
                                        ->count();
                                }

                                if ($isCurrent && $attendanceDateRange[0] && $attendanceDateRange[1] && ! $shift->exclude_from_auto_absence) {
                                    $assignedEmployeeIds = \App\Models\EmployeeProjectRecord::query()
                                        ->where('zone_id', $zone->id)
                                        ->where('shift_id', $shift->id)
                                        ->where('status', true)
                                        ->where(function ($q) use ($now) {
                                            $q->whereNull('end_date')
                                                ->orWhere('end_date', '>=', $now->toDateString());
                                        })
                                        ->pluck('employee_id')
                                        ->toArray();

                                    $presentEmployeeIds = $shift->attendances()
                                        ->whereBetween('created_at', $attendanceDateRange)
                                        ->where('zone_id', $zone->id)
                                        ->where('status', 'present')
                                        ->whereNull('check_out')   // ← حاضر "الآن"
                                        ->pluck('employee_id')
                                        ->toArray();

                                    // بصمة[5]: استخدم نفس منطق النافذة لكن على مستوى الـ Shift لتحديد المغطّين "الآن" في أي Zone
                                    // - إن كانت الوردية الحالية من "اليوم" → نأخذ من بداية اليوم
                                    // - إن كانت من "أمس" → نأخذ coverageWindowStartZone (تشمل قبل منتصف الليل حتى 13 ساعة)
                                    $coverageWindowStartForShift = ($startedAt === 'yesterday')
                                        ? $coverageWindowStartZone
                                        : $now->copy()->startOfDay();

                                    $coveredEmployeeIds = \App\Models\Attendance::query()
                                        ->where('is_coverage', true)
                                        ->whereNull('check_out')  // ← تغطية "نشطة الآن"
                                        ->whereRaw('COALESCE(check_in_datetime, created_at) >= ?', [$coverageWindowStartForShift])
                                        ->pluck('employee_id')
                                        ->toArray();

                                    $missingIds = array_diff($assignedEmployeeIds, array_merge($presentEmployeeIds, $coveredEmployeeIds));

                                    // حفظهم في المصفوفة المجمعة
                                    $missingMap[] = [
                                        'shift_id'    => $shift->id,
                                        'zone_id'     => $zone->id,
                                        'project_id'  => $zone->project_id,
                                        'employee_ids'=> array_values($missingIds),
                                    ];
                                }

                                $shiftItem = [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrent,
                                    'attendees_count' => $attendeesCount,
                                    'emp_no' => $shift->emp_no,
                                ];

                                if ($shift->exclude_from_auto_absence) {
                                    $shiftItem['exclude_from_auto_absence'] = true;
                                }

                                $activeShifts[] = $shiftItem;

                                if ($isCurrent) {
                                    $currentShiftEmpNo += $shift->emp_no;
                                    $allCurrentShiftsAttendeesCount += $attendeesCount;
                                }
                            }

                            // بصمة[6]: out_of_zone كما هي
                            $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'present')
                                ->whereNull('check_out')
                                ->whereDate('date', $now->toDateString())
                                ->whereHas('employee', function ($query) {
                                    $query->where('out_of_zone', true);
                                })
                                ->count();

                            // 🟢 حساب أقدم وقت بداية وردية نشطة (كما هو)
                            $earliestStart = null;
                            foreach ($zone->shifts as $shift) {
                                [$isCurrent, $startedAt] = $shift->getShiftActiveStatus2($now);
                                if (! $isCurrent) continue;

                                $baseDate = $startedAt === 'yesterday'
                                    ? $now->copy()->subDay()->startOfDay()
                                    : $now->copy()->startOfDay();

                                $shiftType = $shift->getShiftTypeAttribute(); // 1 = صباح، 2 = مساء

                                $startTime = match ($shiftType) {
                                    1 => $shift->morning_start,
                                    2 => $shift->evening_start,
                                    default => null,
                                };

                                if (! $startTime) continue;

                                $shiftStart = Carbon::parse("{$baseDate->toDateString()} {$startTime}", 'Asia/Riyadh');

                                if (! $earliestStart || $shiftStart->lt($earliestStart)) {
                                    $earliestStart = $shiftStart;
                                }
                            }

                            // 🟢 تحديد وقت بداية النقص الفعلي لعرضه (كما هو)
                            $unattendedStart = null;
                            if ($zone->last_unattended_started_at) {
                                $unattendedStart = $zone->last_unattended_started_at->gt($earliestStart ?? now())
                                    ? $zone->last_unattended_started_at
                                    : $earliestStart;
                            } elseif ($earliestStart) {
                                $unattendedStart = $earliestStart;
                            }

                            // بصمة[7]: عدّ التغطيات "النشطة الآن" بنفس نافذة الـ Zone
                            $active_coverages_count = \App\Models\Attendance::query()
                                ->where('zone_id', $zone->id)
                                ->where('status', 'coverage')
                                ->where('is_coverage', true)
                                ->whereNull('check_out') // نشطة الآن
                                ->whereRaw('COALESCE(check_in_datetime, created_at) >= ?', [$coverageWindowStartZone])
                                ->count();

                            $missingCount = max(0, $currentShiftEmpNo - ($allCurrentShiftsAttendeesCount + $active_coverages_count));

                            return array_merge([
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $activeShifts,
                                'current_shift_emp_no' => $currentShiftEmpNo,
                                'active_coverages_count' => $active_coverages_count,
                                'out_of_zone_count' => $outOfZoneCount, // مخصص لاحقًا إذا عندك منطق خاص به
                            ], array_filter([
                                'unattended_duration_start' => $missingCount > 0 ? $unattendedStart : null,
                            ]));
                        }),
                    ];
                }),
            ];
        })->toArray();

        // ⬅️ هنا مكان الكاش الصحيح بعد جمع $missingMap بالكامل
        cache()->put('missing_employees_summary_' . $now->toDateString(), $missingMap, now()->addMinutes(3));

        return $summary;
    });
}


    public function getActiveShiftsSummaryV2(?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->tz('Asia/Riyadh') : Carbon::now('Asia/Riyadh');

        $intervalSeconds = 60; // ← 🟡 يمكنك تغييرها إلى 60 أو 120 أو 15 حسب ما تريد

        // $intervalKey = floor($now->timestamp / $intervalSeconds); // ← مفتاح فريد لكل فترة زمنية

        $cacheKey = 'active_shifts_summary';

        // now()->addSeconds(30)
        // now()->addMinutes(1)
        // if (cache()->has($cacheKey)) {
        //     dd("📦 يستخدم الكاش: $cacheKey");
        // }

        return cache()->remember($cacheKey, now()->addSeconds($intervalSeconds), function () use ($now) {
            logger('🚀 تنفيذ فعلي للدالة getActiveShiftsSummaryV2');
            $today = $now->copy()->startOfDay();
            $yesterday = $now->copy()->subDay()->startOfDay();

            $rangeToday = [$today, $now->copy()->endOfDay()];
            $rangeYesterday = [$yesterday, $now->copy()->endOfDay()];
            $coveredToday = \App\Models\Attendance::query()
                ->where('is_coverage', true)
                ->whereBetween('created_at', $rangeToday)
                ->pluck('employee_id')
                ->toArray();

            $coveredYesterday = \App\Models\Attendance::query()
                ->where('is_coverage', true)
                ->whereBetween('created_at', $rangeYesterday)
                ->pluck('employee_id')
                ->toArray();

            $missingMap = [];
            $summary = Area::with(['projects.zones.shifts' => function ($q) {
                $q->where('status', 1); // فقط الورديات النشطة
            }, 'projects.zones' => function ($q) {
                $q->where('status', 1); // فقط المواقع النشطة
            }, 'projects' => function ($q) {
                $q->where('status', 1); // فقط المشاريع النشطة
            }])->get()->map(function ($area) use (&$missingMap, $coveredToday, $coveredYesterday, $now) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'projects' => $area->projects->map(function ($project) use (&$missingMap, $coveredToday, $coveredYesterday, $now) {
                        return [
                            'id' => $project->id,
                            'name' => $project->name,
                            'emp_no' => $project->emp_no,
                            'zones' => $project->zones->map(function ($zone) use (&$missingMap, $coveredToday, $coveredYesterday, $now) {
                                $activeShifts = [];
                                $currentShiftEmpNo = 0;
                                $allCurrentShiftsAttendeesCount = 0;

                                foreach ($zone->shifts as $shift) {
                                    [$isCurrent, $startedAt] = $shift->getShiftActiveStatus2($now); // ← ✅

                                    $attendanceDateRange = match ($startedAt) {
                                        'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                                        'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->endOfDay()],
                                        default => [null, null], // غير نشط
                                    };

                                    $attendeesCount = 0;
                                    if ($attendanceDateRange[0] && $attendanceDateRange[1]) {
                                        $attendeesCount = $shift->attendances()
                                            ->whereBetween('created_at', $attendanceDateRange)
                                            ->where('shift_id', $shift->id)
                                            ->where('zone_id', $zone->id)
                                            ->where('status', 'present')
                                            ->whereNull('check_out')
                                            ->count();
                                    }

                                    if ($isCurrent && $attendanceDateRange[0] && $attendanceDateRange[1] && ! $shift->exclude_from_auto_absence) {
                                        $assignedEmployeeIds = \App\Models\EmployeeProjectRecord::query()
                                            ->where('zone_id', $zone->id)
                                            ->where('shift_id', $shift->id)
                                            ->where('status', true)
                                            ->where(function ($q) use ($now) {
                                                $q->whereNull('end_date')
                                                    ->orWhere('end_date', '>=', $now->toDateString());
                                            })
                                            ->pluck('employee_id')
                                            ->toArray();

                                        $presentEmployeeIds = $shift->attendances()
                                            ->whereBetween('created_at', $attendanceDateRange)
                                            ->where('zone_id', $zone->id)
                                            ->where('status', 'present')
                                            ->whereNull('check_out')
                                            ->pluck('employee_id')
                                            ->toArray();

                                        // $coveredEmployeeIds = \App\Models\Attendance::query()
                                        //     // ->where('zone_id', $zone->id)
                                        //     ->where('is_coverage', true)
                                        //     ->whereBetween('created_at', $attendanceDateRange)
                                        //     ->pluck('employee_id')
                                        //     ->toArray();
                                        $coveredEmployeeIds = match ($startedAt) {
                                            'today' => $coveredToday,
                                            'yesterday' => $coveredYesterday,
                                            default => [],
                                        };

                                        $missingIds = array_diff($assignedEmployeeIds, array_merge($presentEmployeeIds, $coveredEmployeeIds));

                                        // حفظهم في المصفوفة المجمعة
                                        $missingMap[] = [
                                            'shift_id' => $shift->id,
                                            'zone_id' => $zone->id,
                                            'project_id' => $zone->project_id,
                                            'employee_ids' => array_values($missingIds),
                                        ];
                                    }
                                    $shiftItem = [
                                        'id' => $shift->id,
                                        'name' => $shift->name,
                                        'type' => $shift->type,
                                        'is_current_shift' => $isCurrent,
                                        'attendees_count' => $attendeesCount,
                                        'emp_no' => $shift->emp_no,
                                    ];
                                   

                                    // نضيف الحقل فقط إذا كان مستثنًى
                                    if ($shift->exclude_from_auto_absence) {
                                        $shiftItem['exclude_from_auto_absence'] = true;
                                    }

                                    $activeShifts[] = $shiftItem;

                                    if ($isCurrent) {
                                        $currentShiftEmpNo += $shift->emp_no;
                                        $allCurrentShiftsAttendeesCount += $attendeesCount;
                                    }
                                }

                                $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                    ->where('status', 'present')
                                    ->whereNull('check_out')
                                    ->whereDate('date', $now->toDateString())
                                    ->whereHas('employee', function ($query) {
                                        $query->where('out_of_zone', true);
                                    })
                                    ->count();
                                //                                 $outsideAfterMinutes = 1;                 // 👈 عدّلها متى شئت
                                // $threshold = $now->copy()->subMinutes($outsideAfterMinutes);

                                // $outOfZoneCount = \App\Models\Attendance::query()
                                //     ->where('zone_id',    $zone->id)
                                //     ->where('status',     'present')
                                //     ->whereNull('check_out')
                                //     ->whereDate('date',   $now->toDateString())
                                //     ->join('employee_statuses as es', 'es.employee_id', '=', 'attendances.employee_id')
                                //     ->where(function ($q) use ($threshold) {

                                //         // ↙️ 1) خارج الحدود المكانية منذ أكثر من X دقيقة
                                //         $q->where(function ($q1) use ($threshold) {
                                //                $q1->where('es.is_inside', false)
                                //                   ->where('es.last_seen_at', '<', $threshold);
                                //            })

                                //         // ↙️ 2) GPS مُعطّل منذ أكثر من X دقيقة
                                //           ->orWhere(function ($q2) use ($threshold) {
                                //                $q2->where('es.gps_enabled', false)
                                //                   ->where('es.last_gps_status_at', '<', $threshold);
                                //            })

                                //         // ↙️ 3) لم يُرصد آخر ظهور منذ 20 دقيقة (يمكنك تكييفها أو إزالتها)
                                //           ->orWhere('es.last_seen_at', '<', $now->copy()->subMinutes(20));
                                //     })
                                //     // ->distinct('attendances.employee_id')   // فعّلها إن وُجِد احتمال تسجيلين للموظف
                                //     ->count();




                                // 🟢 حساب أقدم وقت بداية وردية نشطة
                                $earliestStart = null;
                                foreach ($zone->shifts as $shift) {
                                    [$isCurrent, $startedAt] = $shift->getShiftActiveStatus2($now);
                                    if (! $isCurrent) continue;

                                    $baseDate = $startedAt === 'yesterday'
                                        ? $now->copy()->subDay()->startOfDay()
                                        : $now->copy()->startOfDay();

                                    $shiftType = $shift->getShiftTypeAttribute(); // 1 = صباح، 2 = مساء

                                    $startTime = match ($shiftType) {
                                        1 => $shift->morning_start,
                                        2 => $shift->evening_start,
                                        default => null,
                                    };

                                    if (! $startTime) continue;

                                    $shiftStart = Carbon::parse("{$baseDate->toDateString()} {$startTime}", 'Asia/Riyadh');

                                    if (! $earliestStart || $shiftStart->lt($earliestStart)) {
                                        $earliestStart = $shiftStart;
                                    }
                                }

                                // 🟢 تحديد وقت بداية النقص الفعلي لعرضه
                                $unattendedStart = null;
                                if ($zone->last_unattended_started_at) {
                                    $unattendedStart = $zone->last_unattended_started_at->gt($earliestStart ?? now())
                                        ? $zone->last_unattended_started_at
                                        : $earliestStart;
                                } elseif ($earliestStart) {
                                    $unattendedStart = $earliestStart;
                                }


                                $active_coverages_count  =$zone->attendances()
                                        // ->whereNull('shift_id')
                                        ->where('status', 'coverage')
                                        ->whereNull('check_out')
                                        ->whereDate('created_at', $now->toDateString())
                                        ->count();

                              $missingCount = max(0, $currentShiftEmpNo - ($allCurrentShiftsAttendeesCount + $active_coverages_count));

                                return array_merge([
                                    'id' => $zone->id,
                                    'name' => $zone->name,
                                    'emp_no' => $zone->emp_no,
                                    'shifts' => $activeShifts,
                                    'current_shift_emp_no' => $currentShiftEmpNo,
                                    'active_coverages_count' => $active_coverages_count,
                                    'out_of_zone_count' => $outOfZoneCount, // مخصص لاحقًا إذا عندك منطق خاص به
                                ], array_filter([
                                    'unattended_duration_start' => $missingCount > 0 ? $unattendedStart : null,
                                ]));
                            }),
                        ];
                    }),
                ];
            })->toArray();
            // ⬅️ هنا مكان الكاش الصحيح بعد جمع $missingMap بالكامل
            cache()->put('missing_employees_summary_' . $now->toDateString(), $missingMap, now()->addMinutes(3));

            return $summary;
        });
    }

    public function getActiveShiftsSummary(?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->tz('Asia/Riyadh') : Carbon::now('Asia/Riyadh');

        return Area::with(['projects.zones.shifts' => function ($q) {
            $q->where('status', 1); // فقط الورديات النشطة
        }, 'projects.zones' => function ($q) {
            $q->where('status', 1); // فقط المواقع النشطة
        }, 'projects' => function ($q) {
            $q->where('status', 1); // فقط المشاريع النشطة
        }])->get()->map(function ($area) use ($now) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($now) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'emp_no' => $project->emp_no,
                        'zones' => $project->zones->map(function ($zone) use ($now) {
                            $activeShifts = [];
                            $currentShiftEmpNo = 0;

                            foreach ($zone->shifts as $shift) {
                                $isCurrent = $shift->isCurrentlyActiveV2($now);

                                $activeShifts[] = [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrent,
                                    'attendees_count' => $shift->attendances()
                                        ->whereDate('created_at', $now->toDateString())
                                        ->where('shift_id', $shift->id)
                                        ->where('zone_id', $zone->id)
                                        ->where('status', 'present')
                                        // ->where('date', $shiftInfo['attendance_date'] ?? null)
                                        ->whereNull('check_out')
                                        ->count(),
                                    'emp_no' => $shift->emp_no,
                                ];

                                if ($isCurrent) {
                                    $currentShiftEmpNo += $shift->emp_no;
                                }
                            }

                            $outOfZoneCount = \App\Models\Attendance::where('zone_id', $zone->id)
                                ->where('status', 'present')
                                ->whereNull('check_out')
                                ->whereDate('date', $now->toDateString())
                                ->whereHas('employee', function ($query) {
                                    $query->where('out_of_zone', true);
                                })
                                ->count();

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $activeShifts,
                                'current_shift_emp_no' => $currentShiftEmpNo,
                                'active_coverages_count' => $zone->attendances()
                                    // ->whereNull('shift_id')
                                    ->where('status', 'coverage')
                                    ->whereNull('check_out')
                                    ->whereDate('created_at', $now->toDateString())
                                    ->count(),
                                'out_of_zone_count' => $outOfZoneCount, // مخصص لاحقًا إذا عندك منطق خاص به
                            ];
                        }),
                    ];
                }),
            ];
        })->toArray();
    }
}
