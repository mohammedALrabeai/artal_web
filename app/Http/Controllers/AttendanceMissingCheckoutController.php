<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceMissingCheckoutController extends Controller
{
    public function index(Request $request)
    {
        // ✅ التوقيت المعتمد
        $now = Carbon::now('Asia/Riyadh');

        // ✅ التاريخ المطلوب (افتراضي: أمس بتوقيت الرياض)
        $date = $request->date
            ? Carbon::parse($request->date, 'Asia/Riyadh')->toDateString()
            : $now->copy()->subDay()->toDateString();

        // ✅ حد الـ 12 ساعة بالنسبة للآن
        $threshold = $now->copy()->subHours(12)->format('Y-m-d H:i:s');

        // (اختياري) ترقيم الصفحات
        $perPage = (int) $request->get('per_page', 50);

        $base = Attendance::query()
            ->leftJoin('employees as emp', 'emp.id', '=', 'attendances.employee_id')
            ->leftJoin('zones as z', 'z.id', '=', 'attendances.zone_id')
            ->leftJoin('projects as p', 'p.id', '=', 'z.project_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'attendances.shift_id')
            ->whereDate('attendances.date', $date)
            ->whereNull('attendances.check_out')
            ->whereNotNull('attendances.check_in')
            ->whereIn('attendances.status', ['present', 'coverage'])
            // ✅ أقدم من 12 ساعة بالنسبة للآن
            ->whereRaw("TIMESTAMP(CONCAT(attendances.`date`,' ',attendances.`check_in`)) <= ?", [$threshold])
            // ✅ تحقق الإسناد النشط (status=true) وغير مستثنى، على نفس (employee, zone), وفي نفس التاريخ
            ->whereExists(function ($q) use ($date) {
                $q->select(DB::raw(1))
                    ->from('employee_project_records as epr')
                    ->join('shifts as s', 's.id', '=', 'epr.shift_id')
                    ->whereColumn('epr.employee_id', 'attendances.employee_id')
                    ->whereColumn('epr.zone_id', 'attendances.zone_id')
                    // نشط في تاريخ الحضور المحدّد:
                    ->where(function ($q) use ($date) {
                        $q->whereNull('epr.start_date')
                          ->orWhereDate('epr.start_date', '<=', $date);
                    })
                    ->where(function ($q) use ($date) {
                        $q->whereNull('epr.end_date')
                          ->orWhereDate('epr.end_date', '>=', $date);
                    })
                    // حالة الإسناد الفعلية لديك:
                    ->where('epr.status', true)
                    // استبعاد الورديات المستثناة:
                    ->where('s.exclude_from_auto_absence', false);
            });

        // فرز افتراضي: الأقدم أولاً
        $base->orderBy('attendances.date')->orderBy('attendances.check_in');

        // الحقول المعادة
        $select = [
            'attendances.id as attendance_id',
            'attendances.employee_id',
            'attendances.zone_id',
            'attendances.shift_id',
            'attendances.status',
            'attendances.date',
            'attendances.check_in',
            DB::raw("TIMESTAMP(CONCAT(attendances.`date`,' ',attendances.`check_in`)) as check_in_datetime"),

            'emp.first_name',
            'emp.father_name',
            'emp.family_name',
            'emp.mobile_number',

            'p.id as project_id',
            'p.name as project_name',

            'z.name as zone_name',
            // 'z.map_url as map_url',

            'sh.name as shift_name',
        ];

        // حساب الساعات المنقضية (اختياري مفيد للواجهة)
        $select[] = DB::raw("ROUND(TIMESTAMPDIFF(MINUTE, TIMESTAMP(CONCAT(attendances.`date`,' ',attendances.`check_in`)), '{$now->format('Y-m-d H:i:s')}')/60, 2) as elapsed_hours");

        // ترقيم الصفحات أو بدون
        if ($perPage > 0) {
            $rows = $base->select($select)->paginate($perPage);
            $data = $rows->items();
        } else {
            $data = $base->select($select)->get();
            $rows = null;
        }

        // تحويل بسيط للاسم الكامل
        $data = collect($data)->map(function ($r) {
            $employeeName = trim(implode(' ', array_filter([
                $r->first_name ?? null,
                $r->father_name ?? null,
                $r->family_name ?? null,
            ])));

            return [
                'attendance_id'   => (int) $r->attendance_id,
                'employee_id'     => (int) $r->employee_id,
                'employee_name'   => $employeeName !== '' ? $employeeName : null,
                'mobile_number'   => $r->mobile_number,

                'project_id'      => $r->project_id ? (int) $r->project_id : null,
                'project_name'    => $r->project_name,
                'zone_name'       => $r->zone_name,
                'shift_name'      => $r->shift_name,

                'status'          => $r->status, // present | coverage
                'date'            => $r->date,
                'check_in'        => $r->check_in,
                'check_in_datetime'=> $r->check_in_datetime,
                'elapsed_hours'   => (float) $r->elapsed_hours,
                'map_url'         => null,
            ];
        })->values();

        return response()->json([
            'date'    => $date,
            'now'     => $now->toDateTimeString(),
            'data'    => $data,
            'pagination' => $rows ? [
                'total'        => $rows->total(),
                'per_page'     => $rows->perPage(),
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
            ] : null,
        ]);
    }
}
