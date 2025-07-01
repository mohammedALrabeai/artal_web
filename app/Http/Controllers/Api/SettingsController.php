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
        $userAgent = strtolower(request()->header('User-Agent', 'unknown'));

        if (str_contains($userAgent, 'android')) {
            $platform = 'android';
        } elseif (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') || str_contains($userAgent, 'ios')) {
            $platform = 'ios';
        } elseif (str_contains($userAgent, 'windows')) {
            $platform = 'windows';
        } else {
            $platform = 'unknown';
        }

        logger()->info('Settings retrieved from platform: ' . $platform, [
            'user_agent' => $userAgent,
            'ip' => request()->ip(),
            'time' => now()->toDateTimeString(),
        ]);
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
