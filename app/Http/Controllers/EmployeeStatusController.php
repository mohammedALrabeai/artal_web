<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Zone;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Shift;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeStatusController extends Controller
{

    // ⚙️ عتبة الثبات (دقائق)
    private const STATIONARY_MINUTES = 10;
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

            // ✅ الفصل بين داخل/خارج النطاق
            $status->is_stationary_inside  = (bool) ($status->is_stationary && $status->is_inside);
            $status->is_stationary_outside = (bool) ($status->is_stationary && !$status->is_inside);


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

    $employeeId = (int) $employee->id;

    // ✅ التتبّع فقط لموظف رقم 1
    $TRACE_ENABLED    = ($employeeId === 1);
    $TRACE_FINGERPRINT = '[#E1-TRACE:ISIN]';
    $TRACE_REQ_ID      = Str::uuid()->toString();

    $trace = function (string $message, array $ctx = []) use ($TRACE_ENABLED, $TRACE_FINGERPRINT, $TRACE_REQ_ID) {
        if ($TRACE_ENABLED) {
            Log::info("{$TRACE_FINGERPRINT} [{$TRACE_REQ_ID}] {$message}", $ctx);
        }
    };

    $trace('BEGIN updateStatus');

    $gpsEnabled          = $request->boolean('gps_enabled', false);
    $isInsideFromRequest = $request->boolean('is_inside', false); // ← يُستخدم فقط عند غياب zone_id
    $lastLocation        = $request->input('last_location');
    $zoneId              = $request->input('zone_id');
    $now                 = Carbon::now('Asia/Riyadh');

    $motionDetected = $request->has('motion_detected')
        ? $request->boolean('motion_detected')
        : null;

    $trace('INPUTS parsed', [
        'employee_id'   => $employeeId,
        'gps_enabled'   => $gpsEnabled,
        'zone_id'       => $zoneId,
        'x_client_source'  => $request->header('X-Client-Source'),
          'x_hb_reason'      => $request->header('X-Heartbeat-Reason'),
        'has_location'  => (bool) $lastLocation,
        'raw_location'  => is_string($lastLocation) ? 'string' : (is_array($lastLocation) ? 'array' : gettype($lastLocation)),
        'motion_flag'   => $motionDetected,   // true/false/null
        'req_is_inside' => $isInsideFromRequest,
        'now'           => (string) $now,
    ]);

    $status = EmployeeStatus::firstOrNew(['employee_id' => $employeeId]);
    $trace('STATUS loaded', [
        'exists'            => $status->exists,
        'prev_last_seen_at' => optional($status->last_seen_at)->toDateTimeString(),
        'prev_gps_enabled'  => $status->gps_enabled,
        'prev_last_gps_at'  => optional($status->last_gps_status_at)->toDateTimeString(),
        'was_is_inside'     => $status->is_inside,
        'prev_last_move_at' => optional($status->last_movement_at)->toDateTimeString(),
        'has_prev_location' => (bool) $status->last_location,
    ]);

    $status->last_seen_at = $now;

    // تحديث حالة GPS
    if ($status->gps_enabled !== $gpsEnabled) {
        $trace('GPS change detected', ['from' => $status->gps_enabled, 'to' => $gpsEnabled]);
        $status->gps_enabled = $gpsEnabled;
        $status->last_gps_status_at = $now;
    }

    // جلب الموقع السابق من الكائن (لا يتم حفظه في الجدول)
    $previousLocation = $status->last_location ?? null;

    // تحديث الموقع الحالي
    if ($lastLocation) {
        $status->last_location = is_array($lastLocation)
            ? $lastLocation
            : json_decode($lastLocation, true);

        $trace('LOCATION updated', [
            'prev_had_location' => (bool) $previousLocation,
            'curr_has_location' => true,
        ]);
    }

    // 🧠 منطق تحديد is_inside مع تتبّع تفصيلي — دون تغيير المنطق
    $trace('INPUTS for inside-check', [
        'zone_id'      => $zoneId,
        'has_location' => (bool) $lastLocation,
        'loc_type'     => is_array($status->last_location) ? 'array' : (is_string($lastLocation) ? 'string' : gettype($lastLocation)),
    ]);

    if ($lastLocation) {
        // تأكد أن last_location مصفوفة
        if (!is_array($status->last_location)) {
            try {
                $status->last_location = json_decode((string)$lastLocation, true);
            } catch (\Throwable $e) {
                $trace('LOCATION parse failed', ['error' => $e->getMessage(), 'last_location' => $lastLocation]);
            }
        }

        if ($zoneId) {
            $zone = Zone::find($zoneId);

            if ($zone && is_array($status->last_location)) {
                // نفترض حقول Zone: lat, longg, area (متر)
                $zoneLat   = (float) $zone->lat;
                $zoneLng   = (float) $zone->longg;
                $zoneAreaM = (float) $zone->area;

                // المسافة الحالية إلى مركز الـ Zone
                [$currDistanceM, $currDistanceOk] = $this->calcDistanceMetersSafe(
                    $status->last_location['latitude'] ?? null,
                    $status->last_location['longitude'] ?? null,
                    $zoneLat,
                    $zoneLng
                );

                // فحص الداخل الحالي وفق دالتك
                $currentInside   = $this->isInsideZone($status->last_location, $zone);
                $previousInside  = false;
                $prevDistanceM   = null;
                $prevDistanceOk  = false;

                if ($previousLocation && is_array($previousLocation)) {
                    [$prevDistanceM, $prevDistanceOk] = $this->calcDistanceMetersSafe(
                        $previousLocation['latitude'] ?? null,
                        $previousLocation['longitude'] ?? null,
                        $zoneLat,
                        $zoneLng
                    );
                    $previousInside = $this->isInsideZone($previousLocation, $zone);
                } else {
                    // منطقك الأصلي: اعتبر السابق true إذا غير متوفر
                    $previousInside = true;
                }

                $finalInside = $currentInside || $previousInside;

                $trace('INSIDE decision (with zone)', [
                    'zone_id'           => $zoneId,
                    'zone_center'       => ['lat' => $zoneLat, 'lng' => $zoneLng],
                    'zone_radius_m'     => $zoneAreaM,
                    'curr_point'        => $status->last_location,
                    'curr_distance_m'   => $currDistanceOk ? round($currDistanceM, 2) : null,
                    'prev_point'        => $previousLocation,
                    'prev_distance_m'   => $prevDistanceOk ? round($prevDistanceM, 2) : null,
                    'currentInside'     => $currentInside,
                    'previousInside'    => $previousInside,
                    'finalInside'       => $finalInside,
                    'was_is_inside'     => $status->is_inside,
                ]);

                if ($status->is_inside !== $finalInside) {
                    $trace('is_inside CHANGED', [
                        'from'   => $status->is_inside,
                        'to'     => $finalInside,
                        'reason' => $currentInside
                            ? 'currentInside=true'
                            : ($previousInside ? 'previousInside=true' : 'both=false'),
                    ]);
                    $status->is_inside = $finalInside;
                } else {
                    $trace('is_inside NO-CHANGE', ['value' => $status->is_inside]);
                }
            } else {
                $trace('ZONE not found or location invalid', [
                    'zone_found'     => (bool) $zone,
                    'location_valid' => is_array($status->last_location),
                ]);
            }
        } else {
            // عدم إرسال zone_id → اعتماد مباشر للقيمة القادمة من التطبيق
            $trace('INSIDE decision (no zone)', [
                'req_is_inside' => $isInsideFromRequest,
                'was_is_inside' => $status->is_inside,
            ]);

            if ($status->is_inside !== $isInsideFromRequest) {
                $trace('is_inside CHANGED (no zone)', [
                    'from' => $status->is_inside,
                    'to'   => $isInsideFromRequest,
                ]);
                $status->is_inside = $isInsideFromRequest;
            } else {
                $trace('is_inside NO-CHANGE (no zone)', ['value' => $status->is_inside]);
            }
        }
    } else {
        $trace('SKIP inside decision: no location in request');
    }

    // الحركة/السكون
    if ($motionDetected === true) {
        $trace('MOTION detected: MOVING');
        $status->last_movement_at = $now;
        $status->is_stationary    = false;
    } elseif ($motionDetected === false) {
        if (!$status->last_movement_at) {
            $trace('MOTION reported STILL but no last_movement_at, initializing', [
                'set_last_movement_at' => (string) $now,
            ]);
            $status->last_movement_at = $now;
            $status->is_stationary    = false;
        } else {
            $minutes = $status->last_movement_at->diffInMinutes($now);
            $status->is_stationary = $minutes >= self::STATIONARY_MINUTES; // مثال: 10 دقائق
            $trace('MOTION reported STILL -> stationary check', [
                'minutes_since_move' => $minutes,
                'threshold_minutes'  => self::STATIONARY_MINUTES,
                'is_stationary'      => $status->is_stationary,
            ]);
        }
    } else {
        $trace('MOTION flag missing (null) — no change');
    }

    $status->save();
    $trace('STATUS saved', [
        'is_inside'        => $status->is_inside,
        'gps_enabled'      => $status->gps_enabled,
        'is_stationary'    => $status->is_stationary,
        'last_seen_at'     => optional($status->last_seen_at)->toDateTimeString(),
        'last_gps_status'  => optional($status->last_gps_status_at)->toDateTimeString(),
        'last_movement_at' => optional($status->last_movement_at)->toDateTimeString(),
    ]);

    $employee = \App\Models\Employee::find($employeeId);
    if ($employee && $status->is_inside === true) {
        $employee->updateQuietly([
            'out_of_zone' => false,
            'last_active' => $now,
        ]);
        $trace('EMPLOYEE updatedQuietly because is_inside=true', [
            'out_of_zone' => false,
            'last_active' => (string) $now,
        ]);
    }

    $trace('END updateStatus');

    return response()->json(['message' => 'Employee status updated successfully']);
}

/**
 * يحسب المسافة بالمتر بين نقطتين (lat,lng). يعيد [distance, ok]
 */
private function calcDistanceMetersSafe($lat1, $lng1, $lat2, $lng2): array
{
    if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
        return [null, false];
    }
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return [$earthRadius * $c, true];
}



    protected function isInsideZone($location, Zone $zone): bool
    {
        if (is_string($location)) {
            $location = json_decode($location, true);
        }

        // 👈 fallback ذكي بدون normalize شامل
        $lat1 = Arr::get($location, 'lat')  ?? Arr::get($location, 'latitude');
        $lon1 = Arr::get($location, 'long') ?? Arr::get($location, 'lng') ?? Arr::get($location, 'longitude');

        if (!is_numeric($lat1) || !is_numeric($lon1)) {
            return true; // نفس سلوكك الوقائي السابق
        }

        $lat1 = (float) $lat1;
        $lon1 = (float) $lon1;

        $lat2   = (float) $zone->lat;
        $lon2   = (float) $zone->longg;
        $radius = $zone->area ?? 50;

        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }




    public function getEmployeeStatusInActiveShifts()
    {
        $enabledShifts = Shift::with(['zone.pattern'])
            ->where('status', true)
            // ->where('exclude_from_auto_absence', false)
            ->get();

        $activeShiftIds = [];

        foreach ($enabledShifts as $shift) {
            [$isActive] = $shift->getShiftActiveStatus2(now());
            if ($isActive) {
                $activeShiftIds[] = $shift->id;
            }
        }

        if (empty($activeShiftIds)) {
            return response()->json([
                'data' => [],
                'message' => 'لا توجد ورديات حالية نشطة.',
            ]);
        }

        $shiftIdsStr = implode(',', $activeShiftIds);
        $limit = (int) request('renewal_limit_minutes', 0); // 0 = لا فلترة


        $results = DB::select("
        SELECT 
            es.id,
            es.employee_id,
            CONCAT(e.first_name, ' ', e.father_name, ' ', e.grandfather_name, ' ', e.family_name) AS full_name,
            e.mobile_number,
            es.last_seen_at,
            es.gps_enabled,
            es.last_gps_status_at,
            es.last_location,
            es.created_at,
            es.updated_at,
            es.is_inside,
            es.notification_enabled,

            es.is_stationary,
            es.last_movement_at,
            es.last_renewal_at,
       TIMESTAMPDIFF(
  MINUTE,
  es.last_renewal_at,
  CONVERT_TZ(NOW(), '+00:00', '+03:00')
) AS minutes_since_last_renewal,

            p.name AS project_name,
            z.name AS zone_name,
            s.name AS shift_name

        FROM employee_statuses es

        JOIN employees e ON e.id = es.employee_id
        JOIN employee_project_records epr ON epr.employee_id = e.id
        JOIN projects p ON p.id = epr.project_id
        JOIN zones z ON z.id = epr.zone_id
        JOIN shifts s ON s.id = epr.shift_id

        WHERE epr.shift_id IN ($shiftIdsStr)
          AND epr.status = true
          AND epr.start_date <= CURDATE()
          AND (epr.end_date IS NULL OR epr.end_date >= CURDATE())

        ORDER BY 
            CASE
                WHEN es.gps_enabled = 0 THEN 1
                WHEN es.is_inside = 0 THEN 2
                WHEN TIMESTAMPDIFF(MINUTE, es.updated_at, NOW()) > 30 THEN 3
                ELSE 4
            END
    ");

        return response()->json([
            'data' => $results,
        ]);
    }



    /**
     * تُعيد مصفوفة قياسية: ['lat' => float, 'long' => float]
     * تقبل مفاتيح: lat/long أو latitude/longitude أو lat/lng
     * وتتعامل مع string/array/JSON.
     */
    private function normalizeLocation($location): ?array
    {
        if (empty($location)) {
            return null;
        }

        if (is_string($location)) {
            $decoded = json_decode($location, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $location = $decoded;
            } else {
                return null;
            }
        }

        if (!is_array($location)) {
            return null;
        }

        // جرّب كل الأسماء المحتملة
        $lat  = $location['lat']
            ?? $location['latitude']
            ?? null;

        $long = $location['long']
            ?? $location['longitude']
            ?? $location['lng']
            ?? null;

        // حوّل إلى float لو كانت قيم نصية
        if ($lat !== null && $long !== null) {
            $lat  = is_numeric($lat)  ? (float) $lat  : null;
            $long = is_numeric($long) ? (float) $long : null;
        }

        if ($lat === null || $long === null) {
            return null;
        }

        return ['lat' => $lat, 'long' => $long];
    }
}
