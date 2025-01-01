<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\EmployeeDevice;


class EmployeeAuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $employee = Employee::where('mobile_number', $request->phone)->where('password', $request->password)->first();

        if (!$employee ) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Cache OTP with expiration (5 minutes)
        // Cache::put('otp_' . $employee->id, $otp, now()->addMinutes(5));
        cache()->put("otp:{$employee->id}", $otp, now()->addMinutes(5));


        // Send OTP via SMS
        $message = "Your OTP code is: $otp";
        $this->otpService->sendOtp($request->phone, $message);
    

        return response()->json([
            'otp' => $otp,
            'message' => 'OTP sent successfully',
            'employee_id' => $employee->id, // لاستخدامه عند التحقق
        ]);
    }

    // public function verifyOtp(Request $request)
    // {
    //     $request->validate([
    //         'employee_id' => 'required|integer',
    //         'otp' => 'required|integer',
    //     ]);

    //     // Check OTP from cache
    //     $cachedOtp = Cache::get('otp_' . $request->employee_id);

    //     if (!$cachedOtp || $cachedOtp != $request->otp) {
    //         return response()->json(['message' => 'Invalid or expired OTP'], 400);
    //     }

    //     $employee = Employee::find($request->employee_id);

    //     if (!$employee) {
    //         return response()->json(['message' => 'Employee not found'], 404);
    //     }

    //     // Generate remember token
    //     $rememberToken = Str::random(60);
    //     $employee->forceFill(['remember_token' => $rememberToken])->save();
    //     $employee->api_token = Str::random(60);
    //     $employee->save();

    //     // Clear OTP from cache
    //     Cache::forget('otp_' . $request->employee_id);

    //     return response()->json([
    //         'message' => 'OTP verified successfully',
    //         'employee' => [
    //             'id' => $employee->id,
    //             'name' => $employee->first_name . ' ' . $employee->family_name,
    //             'remember_token' => $rememberToken,
    //             'api_token' => $employee->api_token
    //         ],
    //     ]);
    // }





    public function verifyOtp(Request $request)
{
    // Check if Authorization header is present
    $token = $request->header('Authorization');
    if ($token) {
        $token = str_replace('Bearer ', '', $token);
        $employee = Employee::where('api_token', $token)->first();

        if ($employee) {
            return response()->json([
                'message' => 'Token is valid',
                'employee' => $employee,
            ]);
        }
    }

    // Existing OTP verification logic
    $employee = Employee::where('id', $request->employee_id)->first();
    

    if (!$employee) {
        return response()->json(['message' => 'Employee not found'], 404);
    }

    // التحقق من كود OTP
    $cachedOtp = cache()->get("otp:{$employee->id}");
    if (!$cachedOtp || $cachedOtp !== $request->otp) {
        return response()->json(['message' => 'Invalid or expired OTP'], 400);
    }

    // حذف OTP من التخزين المؤقت بعد التحقق
    cache()->forget("otp:{$employee->id}");

    // معالجة الجهاز
    $deviceId = $request->device_id;

    // البحث عن الجهاز
    $device = EmployeeDevice::where('device_id', $deviceId)
        ->where('employee_id', $employee->id)
        ->first();

    if (!$device) {
        // التحقق من وجود أجهزة سابقة
        $hasOtherDevices = EmployeeDevice::where('employee_id', $employee->id)->exists();

        if (!$hasOtherDevices) {
            // إذا لم يكن هناك أجهزة، اعتماد الجهاز مباشرةً
            EmployeeDevice::create([
                'employee_id' => $employee->id,
                'device_id' => $deviceId,
                'status' => 'approved',
            ]);

            // تسجيل الدخول
            $apiToken = Str::random(60);
            $employee->update(['api_token' => $apiToken]);

            return response()->json([
                'message' => 'First device approved and login successful',
                'token' => $apiToken,
                'employee' => $employee,
                'new_device_registered' => false,
            ]);
        } else {
            // إذا كان الجهاز جديداً وهناك أجهزة معتمدة سابقاً
            EmployeeDevice::create([
                'employee_id' => $employee->id,
                'device_id' => $deviceId,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'New device registered and is pending approval. Login denied.',
                'employee' => $employee,
                'new_device_registered' => true,
            ], 403); // 403 Forbidden لأن الدخول مرفوض
        }
    }

    // إذا كان الجهاز موجودًا ومعتمدًا
    if ($device->status === 'approved') {
        // تسجيل الدخول
        $apiToken = Str::random(60);
        $employee->update(['api_token' => $apiToken]);

        return response()->json([
            'message' => 'Login successful',
            'token' => $apiToken,
            'employee' => $employee,
            'new_device_registered' => false,
        ]);
    }

    // إذا كان الجهاز في حالة "قيد الموافقة"
    return response()->json([
        'message' => 'Device is pending approval. Login denied.',
        'employee' => $employee,
        'new_device_registered' => true,
    ], 403);
}

public function checkDeviceApproval(Request $request)
{
    $request->validate([
        'employee_id' => 'required|integer',
        'device_id' => 'required|string',
    ]);

    $device = \App\Models\EmployeeDevice::where('employee_id', $request->employee_id)
        ->where('device_id', $request->device_id)
        ->first();

    if (!$device) {
        return response()->json([
            'message' => 'Device not registered for this employee.',
            'status' => 'not_registered',
        ], 404);
    }

    if ($device->status === 'approved') {
        return response()->json([
            'message' => 'Device approved.',
            'status' => 'approved',
        ]);
    }

    if ($device->status === 'pending') {
        return response()->json([
            'message' => 'Device pending approval.',
            'status' => 'pending',
        ]);
    }
}


public function getEmployeeByToken(Request $request)
{
    $token = $request->header('Authorization');

    if (!$token) {
        return response()->json(['message' => 'Token not provided'], 400);
    }

    $employee = Employee::where('api_token', $token)->first();

    if (!$employee) {
        return response()->json(['message' => 'Invalid token'], 401);
    }

    return response()->json([
        'message' => 'Employee information retrieved successfully',
        'employee' => $employee,
    ]);
}

public function updatePlayerId(Request $request)
{
    $request->validate([
        'player_id' => 'required|string',
    ]);

    $employee = auth('employee')->user(); // تغيير من `user` إلى `employee`

    if ($employee) {
        $employee->update(['onesignal_player_id' => $request->player_id]);

        return response()->json(['message' => 'Player ID updated successfully']);
    }

    return response()->json(['message' => 'Employee not authenticated'], 401);
}


}
