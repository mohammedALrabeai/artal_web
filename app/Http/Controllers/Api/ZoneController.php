<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

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
        $zones = Zone::selectRaw("
                zones.*, 
                ( 6371 * acos(
                    cos(radians(?)) 
                    * cos(radians(lat)) 
                    * cos(radians(longg) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(lat))
                ) ) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius) // تحديد النطاق المسموح به
            ->orderBy('distance') // ترتيب النتائج حسب الأقرب
            ->with('project') // جلب معلومات المشروع المرتبط
            ->get();

                // التحقق مما إذا كان الموقع داخل إحدى المناطق
        $zones = $zones->map(function ($zone) use ($latitude, $longitude) {
            // تحديد نصف قطر المنطقة (100 متر مثلاً)
            $zoneRadius = 0.1; // 0.1 كم = 100 متر

            // تحديد ما إذا كانت الإحداثيات داخل المنطقة
            $zone->is_inside = $zone->distance <= $zone->area ;

            return $zone;
        });

        return response()->json([
            'message' => 'Nearby zones fetched successfully.',
            'data' => $zones,
        ], 200);
    }
}
