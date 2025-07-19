<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    /**
     * جلب جميع الإعدادات.
     */
    public function index()
    {

         try {
         // 1. جلب المنصة من الهيدر مباشرة
    $platform = strtolower(request()->header('X-Platform', ''));

    // 2. fallback إذا لم يُرسل الهيدر
    if (empty($platform)) {
        $userAgent = strtolower(request()->header('User-Agent', ''));

        if (str_contains($userAgent, 'android')) {
            $platform = 'android';
        } elseif (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') || str_contains($userAgent, 'ios')) {
            $platform = 'ios';
        } elseif (str_contains($userAgent, 'windows')) {
            $platform = 'windows';
        } else {
            $platform = 'unknown';
        }
    }

    // logger()->info('Settings retrieved from platform: ' . $platform, [
    //     'user_agent' => request()->header('User-Agent'),
    //     'ip' => request()->ip(),
    //     'time' => now()->toDateTimeString(),
    //     'platform' => $platform,
    //     'app_version' => request()->header('X-App-Version'),
    // ]);
    } catch (\Throwable $e) {
        
        // لا تفعل شيئًا حتى لا يتأثر المستخدم
    }
        // الحصول على جميع الإعدادات
        $settings = Setting::pluck('value', 'key')->toArray();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * تحديث الإعدادات.
     */
    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            if (in_array($value, ['true', 'false'], true)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }
}
