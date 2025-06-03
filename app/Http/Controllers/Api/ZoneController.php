<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ZoneController extends Controller
{
    public function nearbyZones(Request $request)
    {
        // التحقق من صحة المدخلات
        $request->validate([
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'radius' => 'nullable|numeric', // النطاق بالكيلومترات، افتراضي 5 كم
        ]);

        // استخراج البيانات من الطلب
        $latitude = $request->lat;
        $longitude = $request->long;
        $radius = $request->radius ?? 5; // افتراضي 5 كم

        // حساب المسافة باستخدام Haversine formula
        $zones = Zone::selectRaw('
                zones.*, 
                ( 6371 * acos(
                    cos(radians(?)) 
                    * cos(radians(lat)) 
                    * cos(radians(longg) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(lat))
                ) ) AS distance
            ', [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius) // تحديد النطاق المسموح به
            ->orderBy('distance') // ترتيب النتائج حسب الأقرب
            ->with('project') // جلب معلومات المشروع المرتبط

            ->get();

        // التحقق مما إذا كان الموقع داخل إحدى المناطق
        $zones = $zones->map(function ($zone) {
            // تحديد نصف قطر المنطقة (100 متر مثلاً)
            $zoneRadius = 0.1; // 0.1 كم = 100 متر

            // تحديد ما إذا كانت الإحداثيات داخل المنطقة
            $zone->is_inside = $zone->distance <= $zone->area;

            return $zone;
        });

        return response()->json([
            'message' => 'Nearby zones fetched successfully.',
            'data' => $zones,
        ], 200);
    }

    public function nearbyZonesWithCurrentShifts(Request $request)
    {
        // ✅ التحقق من صحة المدخلات
        $request->validate([
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'radius' => 'nullable|numeric', // النطاق بالكيلومترات، افتراضي 5 كم
        ]);

        $latitude = $request->lat;
        $longitude = $request->long;
        $radius = $request->radius ?? 5; // افتراضي 5 كم
        $currentTime = Carbon::now('Asia/Riyadh');

        // ✅ جلب المناطق القريبة باستخدام Haversine Formula
        $zones = Zone::selectRaw('
                zones.*, 
                ( 6371 * acos(
                    cos(radians(?)) 
                    * cos(radians(lat)) 
                    * cos(radians(longg) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(lat))
                ) ) AS distance
            ', [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius) // ✅ تحديد النطاق المسموح به
            ->orderBy('distance') // ✅ ترتيب النتائج حسب الأقرب
            ->with(['project', 'shifts.attendances']) // ✅ تحميل المشروع والورديات
            ->where('status', 1)
            ->get();

        // ✅ معالجة البيانات وإرجاعها
        $zones = $zones->map(function ($zone) use ($currentTime) {
            return [
                'id' => $zone->id,
                'project_id' => $zone->project ? $zone->project->id : null,
                'name' => $zone->name,
                'start_date' => $zone->start_date,
                'pattern_id' => $zone->pattern_id,
                'lat' => round($zone->lat, 6), // ✅ تقليل عدد الأرقام العشرية
                'longg' => round($zone->longg, 6),
                'area' => $zone->area,
                'emp_no' => $zone->emp_no,
                'status' => $zone->status,
                'created_at' => $zone->created_at,
                'updated_at' => $zone->updated_at,
                'distance' => round($zone->distance, 3), // ✅ تقليل عدد الأرقام العشرية
                'is_inside' => $zone->distance <= $zone->area,

                // ✅ إضافة المشروع كما كان في البيانات السابقة
                'project' => $zone->project ? [
                    'id' => $zone->project->id,
                    'name' => $zone->project->name,
                    'description' => $zone->project->description,
                    'area_id' => $zone->project->area_id,
                    'start_date' => $zone->project->start_date,
                    'end_date' => $zone->project->end_date,
                    'created_at' => $zone->project->created_at,
                    'updated_at' => $zone->project->updated_at,
                    'emp_no' => $zone->project->emp_no,
                    'hours_no' => $zone->pattern->hours_cat,
                ] : null,

                // ✅ إرجاع الورديات الحالية فقط
                'current_shifts' => $zone->shifts->filter(function ($shift) use ($currentTime) {
                    return $shift->isCurrent($currentTime);
                })->map(function ($shift) {
                    return [
                        'id' => $shift->id,
                        'name' => $shift->name,
                        'type' => $shift->type,
                        'current_shift_type' => $shift->shift_type,
                        'morning_start' => $shift->morning_start,
                        'morning_end' => $shift->morning_end,
                        'evening_start' => $shift->evening_start,
                        'evening_end' => $shift->evening_end,
                        'required_employees' => $shift->emp_no, // ✅ عدد الموظفين المطلوبين
                        'start_date' => $shift->start_date,
                        'present_employees' => $shift->attendances->where('status', 'present')->count(), // ✅ عدد الحاضرين فقط
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'message' => 'Nearby zones with current shifts fetched successfully.',
            'data' => $zones,
        ], 200);
    }


    public function getActiveZonesCoordinates(Request $request)
    {
        $zones = Zone::where('status', 1) // ✅ المواقع الفعالة فقط
            ->select('id', 'lat', 'longg', 'area')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }
}
