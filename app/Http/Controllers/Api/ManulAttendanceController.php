<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProjectRecord;
use App\Models\ManualAttendanceEmployee;
use App\Services\ManualAttendanceRecorder;
use Illuminate\Validation\ValidationException;


class ManulAttendanceController extends Controller
{
    public function getAttendanceData(Request $request)
    {
        $validated = $request->validate([
            'month'   => 'required|date_format:Y-m-d',
            'filters' => 'nullable|array',
        ]);

        $month   = Carbon::parse($validated['month'])->startOfMonth();
        $filters = $validated['filters'] ?? [];

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

        $allEmployees = (clone $baseQuery)
            ->select('manual_attendance_employees.*')
            ->orderBy('epr.project_id', 'asc')
            ->orderBy('epr.zone_id', 'asc')
            ->with([
                'projectRecord' => fn($q) => $q->select('id', 'employee_id', 'project_id', 'zone_id', 'shift_id', 'start_date', 'end_date'),
                'projectRecord.employee' => fn($q) => $q->select('id', 'first_name', 'father_name', 'grandfather_name', 'family_name', 'english_name', 'national_id', 'basic_salary', 'living_allowance', 'other_allowances'),
                'projectRecord.project:id,name',
                'projectRecord.zone:id,name',
                'projectRecord.shift:id,type,start_date,zone_id',
                'projectRecord.shift.zone:id,pattern_id',
                'projectRecord.shift.zone.pattern:id,working_days,off_days',
                'actualZone:id,name',
                'attendances' => fn($q) => $q->whereBetween('date', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ])->with([
                    'replacedRecord.employee:id,first_name,father_name,grandfather_name,family_name,national_id'
                ]), // ✨ [تعديل] تحميل بيانات الموظف البديل
            ])
            ->get();

        if ($allEmployees->isEmpty()) {
            return response()->json(['rows' => []]);
        }

        $rows = $this->formatDataForGrid($allEmployees, $month);

        return response()->json(['rows' => $rows]);
    }

    /**
     * ✅ [تم التعديل] تبني صفّاً واحداً لكل موظف،
     * مع دمج بيانات الحضور والتغطية في كائن واحد لكل يوم.
     */
    private function formatDataForGrid($employees, Carbon $month)
    {
        $daysInMonth   = $month->daysInMonth;
        $firstMonthDay = $month->copy()->startOfMonth();
        $rows          = [];

        foreach ($employees as $employee) {
            $record          = $employee->projectRecord;
            $attKeyed        = $employee->attendances->keyBy(fn($att) => Carbon::parse($att->date)->toDateString()); // ضمان تطابق الفورمات
            $patternCache    = $this->buildPatternCache($record, $firstMonthDay, $daysInMonth);
            $formattedAttend = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayKey   = str_pad($d, 2, '0', STR_PAD_LEFT);
                $dateStr  = $firstMonthDay->copy()->day($d)->toDateString();
                $attRec   = $attKeyed->get($dateStr); // استخدام .get() لتجنب الأخطاء

                $status = $attRec?->status ?? $patternCache[$d];
                $isCoverage   = $attRec?->is_coverage     ?? false;
                $replacedId   = $attRec?->replaced_employee_project_record_id ?? null;
                $replacedName = $attRec?->replacedRecord?->employee
                    ? "{$attRec->replacedRecord->employee->first_name} {$attRec->replacedRecord->employee->family_name}"
                    : null;

                if ($record->start_date && $dateStr < $record->start_date) {
                    $status = 'BEFORE';
                } elseif ($record->end_date && $dateStr > $record->end_date) {
                    $status = 'AFTER';
                }

                // ✨ [تعديل] بناء الكائن الذي يحتوي على كل المعلومات لليوم
                $formattedAttend[$dayKey] = [
                    'status'       => $status,
                    'has_coverage' => $isCoverage,
                    'notes'        => $attRec?->notes ?? '', // إضافة الملاحظات
                    'replaced_record_id'  => $replacedId,
                    'replaced_employee_name' => $replacedName,
                    // 'coverage_employee_id' => $attRec?->coverage_employee_id ?? null, // إضافة معرف الموظف البديل
                ];
            }

            $emp         = $record->employee;
            $arabicName  = "{$emp->first_name} {$emp->father_name} {$emp->grandfather_name} {$emp->family_name}";
            $projectName = $record->project->name ?? '';
            $zoneName    = $record->zone->name ?? '';
            $salary      = ($emp->basic_salary ?? 0)
                + ($emp->living_allowance ?? 0)
                + ($emp->other_allowances ?? 0);

            $rows[] = [
                'id'               => $employee->id, // هذا هو manual_attendance_employee_id
                'name'             => $arabicName,
                'english_name'     => $emp->english_name ?? '',
                'national_id'      => $emp->national_id ?? '',
                'attendance'       => $formattedAttend,
                'project_utilized' => "{$projectName} / {$zoneName}",
                'salary'           => $salary,
            ];
        }

        return collect($rows)->values();
    }

    private function buildPatternCache($record, Carbon $monthStart, int $days): array
    {
        if (!$record->shift?->zone?->pattern) {
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

            if ($idx >= $work) {
                $cache[$d] = 'OFF';
                continue;
            }

            switch ($shiftTyp) {
                case 'morning':
                    $cache[$d] = 'M';
                    break;
                case 'evening':
                    $cache[$d] = 'N';
                    break;
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


    public function assignmentsList(Request $request)
    {
        $search = trim($request->get('q', ''));

        $query = EmployeeProjectRecord::query()
            ->with([
                'employee:id,first_name,father_name,grandfather_name,family_name,national_id,status',
                'project:id,name',
                'zone:id,name',
                'shift:id,name',
            ])
            // موظّفون نشطون فقط
            ->active();

        /* 🔍 البحث */
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                /* الاسم الكامل */
                $q->whereHas('employee', function ($qq) use ($search) {
                    $qq->whereRaw(
                        "CONCAT_WS(' ', first_name, father_name, grandfather_name, family_name) LIKE ?",
                        ["%{$search}%"]
                    );
                })
                    /* رقم الهوية (المعمود نص أو رقم) */
                    ->orWhereHas('employee', fn($qq) =>
                    $qq->where('national_id', 'LIKE', "%{$search}%"))
                    /* اسم المشروع */
                    ->orWhereHas('project', fn($qq) =>
                    $qq->where('name', 'LIKE', "%{$search}%"))
                    /* اسم الموقع/المنطقة */
                    ->orWhereHas('zone', fn($qq) =>
                    $qq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        /* حدّ أقصى 30 نتيجة */
        return $query->limit(30)->get()->map(function ($epr) {
            $emp = $epr->employee;

            return [
                'id'          => $epr->id,                                       // مُعرّف الإسناد
                'name'        => trim("{$emp->first_name} {$emp->father_name} "
                    . "{$emp->grandfather_name} {$emp->family_name}"),
                'national_id' => $emp->national_id,
                'location'    => "{$epr->project->name} / {$epr->zone->name}",
                'shift'       => $epr->shift?->name ?? '—',
                'employee_id' => $epr->employee_id,
            ];
        });
    }



    public function recordAttendance(Request $request, ManualAttendanceRecorder $recorder)
{
    // توثيق المدخلات
    $validated = $request->validate([
        'employee_project_record_id' => 'required|integer|exists:employee_project_records,id',
        'date'        => 'required|date_format:Y-m-d',
        'status'      => 'required|string',
        'zone_id'     => 'nullable|integer|exists:zones,id', // لو أرسلته وكان مختلفًا عن إسناد الموظف: سيُنشأ MAE جديد is_main=false
        'notes'       => 'nullable|string',
        'has_coverage'=> 'nullable|boolean',
        'replaced_record_id' => 'nullable|integer|exists:employee_project_records,id',
    ]);

    try {
        $result = $recorder->record([
            'employee_project_record_id' => (int)$validated['employee_project_record_id'],
            'date'        => $validated['date'],
            'status'      => $validated['status'],
            'zone_id'     => $validated['zone_id'] ?? null,
            'notes'       => $validated['notes'] ?? null,
            'has_coverage'=> (bool)($validated['has_coverage'] ?? false),
            'replaced_record_id' => $validated['replaced_record_id'] ?? null,
            // created_by سيؤخذ من Auth تلقائيًا داخل الخدمة إن لم يُمرّر
        ]);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        // لغايات التتبّع فقط؛ لا تطبع الرسائل الحساسة في الإنتاج
        return response()->json([
            'success' => false,
            'message' => 'Failed to record attendance.',
        ], 500);
    }
}

}
