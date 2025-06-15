<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Arr;
use App\Models\Zone;

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
        // تحديد العتبة الزمنية: 12 ساعة من الآن
        $threshold = now()->subHours(12);

        $employeeStatuses = EmployeeStatus::with([
            // تحميل بيانات الموظف مع الأعمدة المطلوبة فقط
            'employee:id,first_name,father_name,grandfather_name,family_name,mobile_number',
        ])
            ->whereHas('employee.attendances', function ($query) use ($threshold) {
                $query->where(function ($q) use ($threshold) {
                    // شرط التحضير: تحقق من check_in_datetime خلال الـ 12 ساعة الماضية
                    $q->where('check_in_datetime', '>=', $threshold)
                      // أو شرط التغطية: attendance تكون تغطية (is_coverage = true) وتم إنشاؤها خلال الـ 12 ساعة
                        ->orWhere(function ($q2) use ($threshold) {
                            $q2->where('is_coverage', true)
                                ->where('created_at', '>=', $threshold);
                        });
                });
            })
            ->orderByRaw('CASE WHEN gps_enabled = 0 OR last_seen_at < ? THEN 1 ELSE 0 END DESC', [now()->subMinutes(15)])
            ->orderBy('last_seen_at', 'desc')
            ->paginate(100);

        // تحويل بيانات الموظف لتظهر الاسم الكامل ورقم الوظيفة (هنا نستخدم الـ id) ورقم الجوال فقط
        $employeeStatuses->getCollection()->transform(function ($status) {
            if ($status->employee) {
                $employee = $status->employee;
                $status->employee = [
                    'full_name' => trim("{$employee->first_name} {$employee->father_name} {$employee->grandfather_name} {$employee->family_name}"),
                    'job_number' => $employee->id, // أو استخدم حقل آخر إذا كان موجوداً
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
    $employee = Auth::user();
    if (! $employee) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $employeeId = $employee->id;

    $gpsEnabled = $request->boolean('gps_enabled', false);
    $lastLocation = $request->input('last_location'); // يتوقع مصفوفة lat/long
    $zoneId = $request->input('zone_id'); // معرف الموقع المرتبط
    $now = Carbon::now('Asia/Riyadh');

    $status = EmployeeStatus::firstOrNew(['employee_id' => $employeeId]);

    $status->last_seen_at = $now;

    // تحديث حالة GPS
    if ($status->gps_enabled !== $gpsEnabled) {
        $status->gps_enabled = $gpsEnabled;
        $status->last_gps_status_at = $now;
    }

    // الاحتفاظ بالموقع السابق
    $previousLocation = $status->last_location ? json_decode($status->last_location, true) : null;

    // تحديث الموقع الحالي
    if ($lastLocation) {
        if ($previousLocation) {
            $status->previous_location = $status->last_location;
        }
        $status->last_location = is_array($lastLocation)
            ? json_encode($lastLocation)
            : $lastLocation;
    }

    // حساب داخل أو خارج النطاق إذا توفر zone_id وlast_location
    if ($zoneId && $lastLocation) {
        $zone = Zone::find($zoneId);
        if ($zone) {
            $currentInside = $this->isInsideZone($lastLocation, $zone);
            $previousInside = $previousLocation
                ? $this->isInsideZone($previousLocation, $zone)
                : true; // إذا لا يوجد موقع سابق، نعتبره آمن

            $finalInside = $currentInside || $previousInside;

            // إذا تغيرت الحالة فقط
            if ($status->is_inside !== $finalInside) {
                $status->is_inside = $finalInside;
                $status->zone_status_updated_at = $now; // تحتاج تضيف هذا العمود إذا أردت
            }
        }
    }

    $status->save();

    return response()->json(['message' => 'Employee status updated successfully']);
}




public function isInsideZone($lastLocation, Zone $zone): bool
{
    // تحويل last_location إلى مصفوفة إذا كانت JSON
    if (is_string($lastLocation)) {
        $lastLocation = json_decode($lastLocation, true);
    }

    $lat1 = (float) Arr::get($lastLocation, 'lat');
    $lon1 = (float) Arr::get($lastLocation, 'long');

    $lat2 = (float) $zone->lat;
    $lon2 = (float) $zone->longg;
    $radius = $zone->area ?? 50; // ← المسافة المسموحة بالأمتار (افتراضي 50)

    $earthRadius = 6371000; // متر

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return $distance <= $radius;
}

}
