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
            'limit'   => 'required|integer|min:0',
            'filters' => 'nullable|array',
        ]);

        $month   = Carbon::parse($validated['month'])->startOfMonth();
        $filters = $validated['filters'] ?? [];

        /** 2️⃣ بناء الاستعلام الأساسى مع الانضمام لترتيب/فلترة سريعة */
        $baseQuery = ManualAttendanceEmployee::query()
            ->join('employee_project_records as epr', 'manual_attendance_employees.employee_project_record_id', '=', 'epr.id')
            ->where('manual_attendance_employees.attendance_month', $month->toDateString());

        // تطبيق الفلاتر
        if (!empty($filters['projectId'])) {
            $baseQuery->where('epr.project_id', $filters['projectId']);
        }
        if (!empty($filters['zoneId'])) {
            $baseQuery->where('epr.zone_id', $filters['zoneId']);
        }
        if (!empty($filters['shiftId'])) {
            $baseQuery->where('epr.shift_id', $filters['shiftId']);
        }

        /** عدد الموظفين الإجمالي بعد الفلترة */
        $totalEmployees = (clone $baseQuery)->count();

        /** 3️⃣ [تم التعديل] جلب الموظفين المطلوبين مع الترتيب الصحيح من قاعدة البيانات */
        $visibleEmployees = (clone $baseQuery)
            ->select('manual_attendance_employees.*')
            // >> هذا هو الترتيب الأساسي الذي سيتم تطبيقه على كامل الجدول <<
            ->orderBy('epr.project_id', 'asc') // الترتيب حسب معرف المشروع
            ->orderBy('epr.zone_id', 'asc')    // ثم الترتيب حسب معرف الموقع
            ->skip((int) floor($validated['offset'] / 2))
            ->take((int) ceil($validated['limit'] / 2))
            ->get();

        // إذا لم يكن هناك موظفون، أرجع استجابة فارغة مبكراً
        if ($visibleEmployees->isEmpty()) {
            return response()->json(['rows' => [], 'total' => 0]);
        }

        /** 4️⃣ تجميع عدد الحضور/الغياب فى SQL (لا تغيير هنا) */
        // $attendanceCounts = DB::table('manual_attendances')
        //     ->selectRaw('manual_attendance_employee_id AS id, status, COUNT(*) AS c')
        //     ->whereIn('manual_attendance_employee_id', $visibleEmployees->pluck('id'))
        //     ->whereBetween('date', [
        //         $month->copy()->startOfMonth()->toDateString(),
        //         $month->copy()->endOfMonth()->toDateString(),
        //     ])
        //     ->whereIn('status', ['present', 'absent'])
        //     ->groupBy('manual_attendance_employee_id', 'status')
        //     ->get()
        //     ->groupBy('id');

        /** 5️⃣ [تم التعديل] تحميل العلاقات مع الحفاظ على الترتيب الذي تم جلبه */
        $employeeIdsOrdered = $visibleEmployees->pluck('id');

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
            ->whereIn('id', $employeeIdsOrdered)
            // >> الحفاظ على الترتيب باستخدام `orderByRaw` مع `FIELD` <<
            ->orderByRaw("FIELD(manual_attendance_employees.id, " . $employeeIdsOrdered->map(fn($id) => "'$id'")->implode(',') . ")")
            ->get();

        /** 6️⃣ تجهيز الصفوف وتمرير تجميعة الإحصاءات (لا تغيير هنا) */
        $rows = $this->formatDataForGrid($employees, $month, null);

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
            // $empCounts = $counts?->get($employee->id, collect()) ?? collect();
            // $present   = optional($empCounts->firstWhere('status', 'present'))->c ?? 0;
            // $absent    = optional($empCounts->firstWhere('status', 'absent'))->c  ?? 0;

            /** الصف الأول (العربى) */
            $rows[] = [
                'id'               => $employee->id,
                'name'             => $arabicName,
                'national_id'      => $emp->national_id ?? '',
                'attendance'       => $formattedAttend,
                // 'stats'            => ['present' => $present, 'absent' => $absent],
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
                // 'stats'            => ['present' => '', 'absent' => ''],
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

    // ... يمكنك إبقاء دالة getWorkPatternLabel أو حذفها إذا لم تعد مستخدمة في مكان آخر
}
