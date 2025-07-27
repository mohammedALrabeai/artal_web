<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ManualAttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ManulAttendanceController extends Controller
{

      public function getAttendanceData(Request $request)
{
    $validated = $request->validate([
        'month' => 'required|date_format:Y-m-d',
        'offset' => 'required|integer|min:0',
        'limit' => 'required|integer|min:1|max:100',
        'filters' => 'nullable|array'
    ]);

    $month = Carbon::parse($validated['month'])->startOfMonth();
    $filters = $validated['filters'] ?? [];

    // الانضمام لتفعيل الفلترة والترتيب
    $baseQuery = ManualAttendanceEmployee::query()
        ->join('employee_project_records as epr', 'manual_attendance_employees.employee_project_record_id', '=', 'epr.id')
        ->where('attendance_month', $month->toDateString());

    if (!empty($filters['projectId'])) {
        $baseQuery->where('epr.project_id', $filters['projectId']);
    }
    if (!empty($filters['zoneId'])) {
        $baseQuery->where('epr.zone_id', $filters['zoneId']);
    }
    if (!empty($filters['shiftId'])) {
        $baseQuery->where('epr.shift_id', $filters['shiftId']);
    }

    // عدد كل الموظفين (قبل التقسيم لصفين)
    $totalEmployees = (clone $baseQuery)->count();

    // جلب الموظفين المطلوبين فقط مع الترتيب حسب المشروع ثم الموقع
    $visibleEmployees = (clone $baseQuery)
        ->orderBy('epr.project_id')
        ->orderBy('epr.zone_id')
        ->select('manual_attendance_employees.*')
        ->skip((int) floor($validated['offset'] / 2))
        ->take((int) ceil($validated['limit'] / 2))
        ->get();

    // تحميل البيانات مع العلاقات وترتيبها
    $employees = ManualAttendanceEmployee::with([
        'projectRecord' => fn($q) => $q->select('id', 'employee_id', 'project_id', 'zone_id', 'shift_id', 'start_date', 'end_date'),
        'projectRecord.employee' => fn($q) => $q->select('id', 'first_name', 'father_name', 'grandfather_name', 'family_name', 'english_name', 'national_id', 'basic_salary', 'living_allowance', 'other_allowances'),
        'projectRecord.project:id,name',
        'projectRecord.zone:id,name',
        'projectRecord.shift:id,type,start_date,zone_id',
        'projectRecord.shift.zone:id,pattern_id',
        'projectRecord.shift.zone.pattern:id,working_days,off_days',
        'attendances' => fn($q) => $q->whereRaw('DATE(`date`) BETWEEN ? AND ?', [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ])
    ])
        ->whereIn('id', $visibleEmployees->pluck('id'))
        ->get()
        ->sortBy([
            fn($emp) => $emp->projectRecord->project_id,
            fn($emp) => $emp->projectRecord->zone_id,
        ])
        ->values();

    // تجهيز البيانات النهائية
    $rows = $this->formatDataForGrid($employees, $month);

    return response()->json([
        'rows' => $rows,
        'total' => $totalEmployees * 2, // لأن كل موظف له سطرين
    ]);
}

    private function formatDataForGrid($employees, Carbon $month)
    {
        $daysInMonth = $month->daysInMonth;
        $daysArray = range(1, $daysInMonth);
        $rows = [];

        foreach ($employees as $employee) {
            $attendanceByDate = $employee->attendances->keyBy('date');
            $record = $employee->projectRecord;
            $formattedAttendance = [];

            foreach ($daysArray as $day) {
                $currentDateStr = $month->copy()->day($day)->toDateString();
                $attendanceRecord = $attendanceByDate->get($currentDateStr);

                $isBefore = $record->start_date && $currentDateStr < $record->start_date;
                $isAfter = $record->end_date && $currentDateStr > $record->end_date;

                if ($isBefore || $isAfter) {
                    $status = $isBefore ? 'BEFORE' : 'AFTER';
                } else {
                    $workPattern = $this->getWorkPatternLabel($record, $currentDateStr);
                    $status = $attendanceRecord->status ?? $workPattern;
                }

                $dayKey = str_pad($day, 2, '0', STR_PAD_LEFT);
                $formattedAttendance[$dayKey] = $status;
            }

            // الأسماء
            $arabicName = $employee->projectRecord->employee->first_name . ' ' .
                $employee->projectRecord->employee->father_name . ' ' .
                $employee->projectRecord->employee->grandfather_name . ' ' .
                $employee->projectRecord->employee->family_name;

            $englishName = $employee->projectRecord->employee->english_name;

            // معلومات المشروع والموقع
            $projectName = $employee->projectRecord->project->name ?? '';
            $zoneName = $employee->projectRecord->zone->name ?? '';

            // الراتب (يمكنك تعديله حسب مصدرك)
          $salary = ($employee->projectRecord->employee->basic_salary ?? 0)
        + ($employee->projectRecord->employee->living_allowance ?? 0)
        + ($employee->projectRecord->employee->other_allowances ?? 0);
            // ✅ الصف الأول: العربي
            $rows[] = [
                'id' => $employee->id,
                'name' => $arabicName,
                'national_id' => $employee->projectRecord->employee->national_id ?? '',
                'attendance' => $formattedAttendance,
                'stats' => [
                    'present' => $employee->attendances->where('status', 'present')->count(),
                    'absent' => $employee->attendances->where('status', 'absent')->count(),
                ],
                'project_utilized' => $projectName ,
                'salary' => $salary,
                'is_english' => false,
            ];

            // ✅ الصف الثاني: الإنجليزي
            $rows[] = [
                'id' => "{$employee->id}-en",
                'name' => $englishName,
                'national_id' => '',
                'attendance' => [],
                'stats' => [
                    'present' => '',
                    'absent' => '',
                ],
                'project_utilized' => $zoneName,
                'salary' => '',
                'is_english' => true,
            ];
        }

        return collect($rows)->values();
    }



    /**
     * ✅ تم نسخ هذه الدالة مباشرة من الكود القديم.
     * تحسب نمط العمل (M, N, OFF) ليوم محدد.
     */
    public function getWorkPatternLabel($record, $date): string
    {
        if (!$record->shift || !$record->shift->zone || !$record->shift->zone->pattern) {
            return '❌';
        }

        $pattern = $record->shift->zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        if ($cycleLength === 0) return 'ERR'; // تجنب القسمة على صفر

        $startDate = Carbon::parse($record->shift->start_date);
        $targetDate = Carbon::parse($date);
        $totalDays = $startDate->diffInDays($targetDate);
        $currentDayInCycle = $totalDays % $cycleLength;
        $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

        $isWorkDay = $currentDayInCycle < $workingDays;

        if (!$isWorkDay) {
            return 'OFF';
        }

        $type = $record->shift->type;

        return match ($type) {
            'morning' => 'M',
            'evening' => 'N',
            'morning_evening' => ($cycleNumber % 2 == 1 ? 'M' : 'N'),
            'evening_morning' => ($cycleNumber % 2 == 1 ? 'N' : 'M'),
            default => 'M',
        };
    }
}
