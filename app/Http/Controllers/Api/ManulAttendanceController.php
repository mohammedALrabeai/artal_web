<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ManualAttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManulAttendanceController extends Controller
{

    public function getAttendanceData(Request $request)
    {
        /** 1️⃣ التحقق من البيانات الواردة */
        $validated = $request->validate([
            'month'   => 'required|date_format:Y-m-d',
            'offset'  => 'required|integer|min:0',
            'limit'   => 'required|integer|min:1|max:200',
            'filters' => 'nullable|array',
        ]);

        $month   = Carbon::parse($validated['month'])->startOfMonth();
        $filters = $validated['filters'] ?? [];

        /** 2️⃣ بناء الاستعلام الأساسى مع الانضمام لترتيب/فلترة سريعة */
        $baseQuery = ManualAttendanceEmployee::query()
            ->join('employee_project_records as epr', 'manual_attendance_employees.employee_project_record_id', '=', 'epr.id')
            ->where('manual_attendance_employees.attendance_month', $month->toDateString());

        if (!empty($filters['projectId'])) {
            $baseQuery->where('epr.project_id', $filters['projectId']);
        }
        if (!empty($filters['zoneId'])) {
            $baseQuery->where('epr.zone_id', $filters['zoneId']);
        }
        if (!empty($filters['shiftId'])) {
            $baseQuery->where('epr.shift_id', $filters['shiftId']);
        }

        /** عدد الموظفين قبل تقسيم الصفين */
        $totalEmployees = (clone $baseQuery)->count();

        /** 3️⃣ الموظفون المطلوبون لهذه الدفعة (25 صف ⇒ 12.5 موظف تقريباً) */
        $visibleEmployees = (clone $baseQuery)
            ->orderBy('epr.project_id')
            ->orderBy('epr.zone_id')
            ->select('manual_attendance_employees.*')
            ->skip((int) floor($validated['offset'] / 2))
            ->take((int) ceil($validated['limit'] / 2))
            ->get();

        /** 4️⃣ تجميع عدد الحضور/الغياب فى SQL بدلاً من حلقات PHP */
        $attendanceCounts = DB::table('manual_attendances')
            ->selectRaw('manual_attendance_employee_id AS id, status, COUNT(*) AS c')
            ->whereIn('manual_attendance_employee_id', $visibleEmployees->pluck('id'))
            ->whereBetween('date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->whereIn('status', ['present', 'absent'])
            ->groupBy('manual_attendance_employee_id', 'status')
            ->get()
            ->groupBy('id');      // => [ employeeId => Collection( {status,c}, … ) ]

        /** 5️⃣ تحميل علاقات الموظفين وترتيبهم */
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
            ]),
        ])
            ->whereIn('id', $visibleEmployees->pluck('id'))
            ->get()
            ->sortBy([
                fn($emp) => $emp->projectRecord->project_id,
                fn($emp) => $emp->projectRecord->zone_id,
            ])
            ->values();

        /** 6️⃣ تجهيز الصفوف وتمرير تجميعة الإحصاءات */
        $rows = $this->formatDataForGrid($employees, $month, $attendanceCounts);

        /** 7️⃣ الإرجاع للواجهة */
        return response()->json([
            'rows'  => $rows,
            'total' => $totalEmployees * 2,   // كل موظف = صفان
        ]);
    }


  /**
 * تبنى صفَّـين (عربى + إنجليزى) لكل موظف،
 * مع الاعتماد على $counts لتعبئة present / absent بسرعة.
 *
 * @param \Illuminate\Support\Collection        $employees
 * @param \Illuminate\Support\Carbon            $month
 * @param \Illuminate\Support\Collection|null   $counts   // تجميعة SQL {id,status,c}
 * @return \Illuminate\Support\Collection
 */
private function formatDataForGrid($employees, Carbon $month, $counts = null)
{
    $daysInMonth   = $month->daysInMonth;
    $firstMonthDay = $month->copy()->startOfMonth();
    $rows          = [];

    foreach ($employees as $employee) {
        $record          = $employee->projectRecord;
        $attKeyed        = $employee->attendances->keyBy('date');
        $patternCache    = $this->buildPatternCache($record, $firstMonthDay, $daysInMonth);
        $formattedAttend = [];

        /* ▸◂  الحلقــة اليومية السريعة  ▸◂ */
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayKey   = str_pad($d, 2, '0', STR_PAD_LEFT);
            $dateStr  = $firstMonthDay->copy()->day($d)->toDateString();
            $attRec   = $attKeyed[$dateStr] ?? null;

            $status = $attRec?->status ?? $patternCache[$d];

            // BEFORE / AFTER حسب تواريخ الإسناد
            if ($record->start_date && $dateStr < $record->start_date) $status = 'BEFORE';
            elseif ($record->end_date && $dateStr > $record->end_date) $status = 'AFTER';

            $formattedAttend[$dayKey] = $status;
        }

        /* ▸◂  البيانات الثابتة  ▸◂ */
        $emp         = $record->employee;
        $arabicName  = "{$emp->first_name} {$emp->father_name} {$emp->grandfather_name} {$emp->family_name}";
        $englishName = $emp->english_name;
        $projectName = $record->project->name ?? '';
        $zoneName    = $record->zone->name ?? '';
        $salary      = ($emp->basic_salary ?? 0) + ($emp->living_allowance ?? 0) + ($emp->other_allowances ?? 0);

        /* ▸◂  إحصاءات الحضور/الغياب من تجميعة SQL  ▸◂ */
        $empCounts = $counts?->get($employee->id, collect()) ?? collect();
        $present   = optional($empCounts->firstWhere('status', 'present'))->c ?? 0;
        $absent    = optional($empCounts->firstWhere('status', 'absent'))->c  ?? 0;

        /** الصف الأول (العربى) */
        $rows[] = [
            'id'               => $employee->id,
            'name'             => $arabicName,
            'national_id'      => $emp->national_id ?? '',
            'attendance'       => $formattedAttend,
            'stats'            => ['present' => $present, 'absent' => $absent],
            'project_utilized' => $projectName,
            'salary'           => $salary,
            'is_english'       => false,
        ];

        /** الصف الثانى (الإنجليزى) */
        $rows[] = [
            'id'               => "{$employee->id}-en",
            'name'             => $englishName,
            'national_id'      => '',
            'attendance'       => [],
            'stats'            => ['present' => '', 'absent' => ''],
            'project_utilized' => $zoneName,
            'salary'           => '',
            'is_english'       => true,
        ];
    }

    return collect($rows)->values();
}

/**
 * تُنشئ مصفوفة نمط العمل للشهر مرة واحدة لتجنب حسابه يوميًا داخل الحلقة.
 *
 * @return array  مفاتيحها 1..31 والقيم M/N/OFF
 */
private function buildPatternCache($record, Carbon $monthStart, int $days): array
{
    if (! $record->shift?->zone?->pattern) {
        // فى حالة عدم وجود نمط نعيد ❌ لكل الأيام.
        return array_fill(1, $days, '❌');
    }

    $pattern  = $record->shift->zone->pattern;
    $work     = (int) $pattern->working_days;
    $off      = (int) $pattern->off_days;
    $cycle    = $work + $off ?: 1;
    $shiftTyp = $record->shift->type;
    $start    = Carbon::parse($record->shift->start_date);
    $cache    = [];

    for ($d = 1; $d <= $days; $d++) {
        $diff  = $start->diffInDays($monthStart->copy()->day($d));
        $idx   = $diff % $cycle;

        if ($idx >= $work) {        // يوم OFF
            $cache[$d] = 'OFF';
            continue;
        }

        // يوم عمل: حسم الحرف بسرعة
        switch ($shiftTyp) {
            case 'morning':  $cache[$d] = 'M'; break;
            case 'evening':  $cache[$d] = 'N'; break;
            case 'morning_evening':
                $cache[$d] = (int)floor($diff / $cycle) % 2 ? 'N' : 'M';
                break;
            case 'evening_morning':
                $cache[$d] = (int)floor($diff / $cycle) % 2 ? 'M' : 'N';
                break;
            default:
                $cache[$d] = 'M';
        }
    }

    return $cache;
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
