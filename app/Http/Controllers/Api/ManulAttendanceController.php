<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ManualAttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManulAttendanceController extends Controller
{
    /**
     * نقطة النهاية الرئيسية لجلب بيانات الحضور بشكل مجزأ وفعال.
     */
    public function getAttendanceData(Request $request)
    {
        // 1. التحقق من المدخلات الأساسية (الشهر)
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m-d',
            'offset' => 'required|integer|min:0',
            'limit' => 'required|integer|min:1|max:100', // حد أقصى 100 صف في كل طلب
            'filters' => 'nullable|array'
        ]);

        $month = Carbon::parse($validated['month'])->startOfMonth();
        $filters = $validated['filters'] ?? [];

        // 2. بناء الاستعلام الأساسي لجلب IDs الموظفين فقط (سريع جدًا)
        $baseQuery = ManualAttendanceEmployee::query()
            ->where('attendance_month', $month->toDateString());

        // تطبيق الفلاتر (المشروع، الموقع، الوردية)
        if (!empty($filters['projectId'])) {
            $baseQuery->whereHas('projectRecord', fn ($q) => $q->where('project_id', $filters['projectId']));
        }
        if (!empty($filters['zoneId'])) {
            $baseQuery->whereHas('projectRecord', fn ($q) => $q->where('zone_id', $filters['zoneId']));
        }
        if (!empty($filters['shiftId'])) {
            $baseQuery->whereHas('projectRecord', fn ($q) => $q->where('shift_id', '>=', $filters['shiftId']));
        }

        // 3. جلب كل IDs الموظفين المطابقين وحساب العدد الإجمالي
        // نستخدم DB::table للحصول على أداء أفضل في جلب الـ IDs فقط
        $allEmployeeIds = $baseQuery->pluck('id')->toArray();
        $totalEmployees = count($allEmployeeIds);

        // 4. تحديد "النافذة" المطلوبة من الـ IDs
        $visibleEmployeeIds = array_slice($allEmployeeIds, $validated['offset'], $validated['limit']);

        // إذا لم تكن هناك IDs في النافذة، أرجع استجابة فارغة
        if (empty($visibleEmployeeIds)) {
            return response()->json([
                'rows' => [],
                'total' => $totalEmployees,
            ]);
        }

        // 5. الآن فقط، جلب البيانات الكاملة للنافذة المحددة بكفاءة
        $employees = ManualAttendanceEmployee::with([
            'projectRecord.employee:id,name', // جلب الأعمدة المطلوبة فقط
            'attendances' => fn ($q) => $q->whereBetween('date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString()
            ])
        ])
        ->whereIn('id', $visibleEmployeeIds)
        ->get();

        // 6. معالجة البيانات وتحويلها إلى صيغة بسيطة جاهزة للعرض
        $rows = $this->formatDataForGrid($employees);

        // 7. إرجاع البيانات النهائية
        return response()->json([
            'rows' => $rows,
            'total' => $totalEmployees,
        ]);
    }

    /**
     * دالة مساعدة لتحويل بيانات الموظفين إلى صيغة مناسبة للجدول.
     */
    private function formatDataForGrid($employees)
    {
        return $employees->map(function ($employee) {
            // تحويل مجموعة الحضور إلى مصفوفة (key-value) للوصول السريع في الواجهة الأمامية
            // المفتاح هو اليوم (e.g., '01', '02') والقيمة هي الحالة
            $attendanceMap = $employee->attendances->keyBy(function ($att) {
                return Carbon::parse($att->date)->format('d');
            })->map->status;

            // حساب الإحصائيات
            $presentCount = $employee->attendances->where('status', 'present')->count();
            $absentCount = $employee->attendances->where('status', 'absent')->count();

            return [
                'id' => $employee->id,
                'name' => $employee->projectRecord->employee->name,
                'attendance' => $attendanceMap,
                'stats' => [
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    // أضف أي إحصائيات أخرى هنا
                ]
            ];
        })->values(); // .values() لإعادة الفهرسة
    }
}
