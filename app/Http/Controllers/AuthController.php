<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\OtpVerification;

class AuthController extends Controller
{
    // تسجيل الدخول
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $employee = Employee::where('mobile_number', $request->phone)->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Invalid credentials.'),
            ], 401);
        }

        // Generate a remember_token
        $token = Str::random(60);
        $employee->remember_token = $token;
        $employee->save();

        // Send OTP
        $otpCode = rand(100000, 999999);
        OtpVerification::updateOrCreate(
            ['phone' => $request->phone],
            ['otp_code' => $otpCode, 'expires_at' => now()->addMinutes(5)]
        );

        sendVerificationCode($request->phone, "Your OTP code is: $otpCode");

        return response()->json([
            'status' => 'success',
            'remember_token' => $token,
            'message' => __('OTP sent successfully.'),
        ]);
    }

    // التحقق من OTP وإرجاع بيانات الموظف
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string',
        ]);

        $verification = OtpVerification::where('phone', $request->phone)->first();

        if (!$verification || $verification->otp_code !== $request->otp_code || now()->greaterThan($verification->expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Invalid or expired OTP.'),
            ], 401);
        }

        // حذف السجل بعد التحقق
        $verification->delete();

        $employee = Employee::where('mobile_number', $request->phone)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => __('Employee not found.'),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'employee' => $employee,
        ]);
    }
}
