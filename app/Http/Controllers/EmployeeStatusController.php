<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeStatusController extends Controller
{
    /**
     * جلب بيانات حالات الموظفين مع بيانات الموظف المختصرة.
     */
    /**
     * استرجاع بيانات حالات الموظفين مع بيانات الموظف المحدودة (الاسم الكامل، رقم الوظيفة ورقم الجوال)
     * وترتيب النتائج بحيث تظهر أولاً الحالات التي يكون فيها GPS مُغلق أو أن آخر تواجد تجاوز 15 دقيقة.
     */
    public function index(Request $request)
    {
        // تحديد العتبة الزمنية: 15 دقيقة من الآن
        $threshold = now()->subMinutes(15);

        $employeeStatuses = EmployeeStatus::with([
            // تحميل بيانات الموظف مع الأعمدة المطلوبة فقط
            'employee:id,first_name,father_name,grandfather_name,family_name,mobile_number',
        ])
            ->orderByRaw('CASE WHEN gps_enabled = 0 OR last_seen_at < ? THEN 1 ELSE 0 END DESC', [$threshold])
            ->orderBy('last_seen_at', 'desc')
            ->paginate(20);

        // تحويل بيانات الموظف لتظهر الاسم الكامل ورقم الوظيفة (هنا نستخدم الـ id) ورقم الجوال فقط
        $employeeStatuses->getCollection()->transform(function ($status) {
            if ($status->employee) {
                $employee = $status->employee;
                $status->employee = [
                    'full_name' => trim("{$employee->first_name} {$employee->father_name} {$employee->grandfather_name} {$employee->family_name}"),
                    'job_number' => $employee->id, // أو استخدم حقل آخر إذا كان موجوداً لرقم الوظيفة
                    'mobile_number' => $employee->mobile_number,
                ];
            }

            return $status;
        });

        return response()->json($employeeStatuses);
    }

    /**
     * تحديث حالة الموظف بناءً على بيانات heartbeat.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        // الحصول على المستخدم المُصادق عليه
        $employee = Auth::user();
        if (! $employee) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $employeeId = $employee->id;

        // استلام البيانات من الطلب
        $gpsEnabled = $request->boolean('gps_enabled', false);
        $isInside = $request->boolean('is_inside', false);
        $lastLocation = $request->input('last_location'); // من المتوقع أن تكون بيانات الموقع JSON أو مصفوفة

        // إيجاد السجل الخاص بالموظف أو إنشاؤه إذا لم يكن موجوداً
        $status = EmployeeStatus::firstOrNew(['employee_id' => $employeeId]);

        // تحديث وقت آخر اتصال
        $status->last_seen_at = Carbon::now();

        // تحديث حالة GPS: إذا تغيرت القيمة يتم تحديث وقت تغيير حالة GPS
        if ($status->gps_enabled !== $gpsEnabled) {
            $status->gps_enabled = $gpsEnabled;
            $status->last_gps_status_at = Carbon::now();
        }

        // تحديث حالة النطاق (is_inside) وإذا تغيرت القيمة يتم تحديث وقت تغيير حالة النطاق
        if ($status->is_inside !== $isInside) {
            $status->is_inside = $isInside;
            // يمكن إضافة عمود آخر مثل zone_status_updated_at إذا أردت تتبع التغيير
            // $status->zone_status_updated_at = Carbon::now();
        }

        // تحديث الموقع إذا تم إرساله
        if ($lastLocation) {
            $status->last_location = is_array($lastLocation)
                ? json_encode($lastLocation)
                : $lastLocation;
        }

        $status->save();

        return response()->json(['message' => 'Employee status updated successfully']);
    }
}
