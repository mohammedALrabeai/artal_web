<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

        if (! $employee) {
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
            } else {
                if (strlen($token) > 10) {
                    return response()->json([
                        'message' => 'Token is invalid',
                    ], 401);
                }
            }
        }

        // Existing OTP verification logic
        $employee = Employee::where('id', $request->employee_id)->first();

        if (! $employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        // اذا كان رقم جوال وكود تحقق الخاص بالفحص المتاجر
        if ($employee->mobile_number == '966571718153' && $request->otp == 253770) {
            $device = EmployeeDevice::where('device_id', 'rtfc')
                ->where('employee_id', $employee->id)->first();

            // تسجيل الدخول
            // $apiToken = Str::random(60);
            // $employee->update(['api_token' => $apiToken]);

            $apiToken = $employee->api_token;

            return response()->json([
                'message' => 'Login successful',
                'token' => $apiToken,
                'employee' => $employee,
                'new_device_registered' => false,
            ]);

        }

        // التحقق من كود OTP
        $cachedOtp = cache()->get("otp:{$employee->id}");
        if (! $cachedOtp || $cachedOtp !== $request->otp) {
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

        if (! $device) {
            // التحقق من وجود أجهزة سابقة
            $hasOtherDevices = EmployeeDevice::where('employee_id', $employee->id)->exists();

            if (! $hasOtherDevices) {
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

        if (! $device) {
            return response()->json([
                'message' => 'Device not registered for this employee.',
                'status' => 'not_registered',
            ], 404);
        }

        if ($device->status === 'approved') {
            $employee = $device->employee;

            return response()->json([
                'employeeStatus' => $employee->status,
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
        // $token = $request->header('Authorization');

        // if (!$token) {
        //     return response()->json(['message' => 'Token not provided'], 400);
        // }
        $employee = auth()->user();

        // $employee = Employee::where('api_token', $token)->first();

        if (! $employee) {
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

    public function changePassword(Request $request)
    {

        $employee = auth()->user();

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed', // تأكيد كلمة المرور الجديدة
        ]);
        if (! $employee) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // التحقق من كلمة المرور القديمة
        if (! ($request->old_password == $employee->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 400);
        }

        // تحديث كلمة المرور
        $employee->update([
            'password' => $request->new_password,
        ]);

        return response()->json(['message' => 'Password changed successfully'], 200);
    }
}
