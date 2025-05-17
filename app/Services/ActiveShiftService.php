<?php

namespace App\Services;

use App\Models\Area;
use Carbon\Carbon;

class ActiveShiftService
{
    public function getActiveShiftsSummaryV2(?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->tz('Asia/Riyadh') : Carbon::now('Asia/Riyadh');
       
        $intervalSeconds = 30; // ← 🟡 يمكنك تغييرها إلى 60 أو 120 أو 15 حسب ما تريد

        $intervalKey = floor($now->timestamp / $intervalSeconds); // ← مفتاح فريد لكل فترة زمنية

        $cacheKey = "active_shifts_summary_{$intervalKey}";

        // now()->addSeconds(30)
        // now()->addMinutes(1)

        return cache()->remember($cacheKey, now()->addSeconds($intervalSeconds), function () use ($now) {
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

                                foreach ($zone->shifts as $shift) {
                                    [$isCurrent, $startedAt] = $shift->getShiftActiveStatus($now); // ← ✅

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

                                    if ($isCurrent && $attendanceDateRange[0] && $attendanceDateRange[1]) {
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

                                    $activeShifts[] = [
                                        'id' => $shift->id,
                                        'name' => $shift->name,
                                        'type' => $shift->type,
                                        'is_current_shift' => $isCurrent,
                                        'attendees_count' => $attendeesCount,
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
            // ⬅️ هنا مكان الكاش الصحيح بعد جمع $missingMap بالكامل
            cache()->put('missing_employees_summary_'.$now->toDateString(), $missingMap, now()->addMinutes(3));

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
