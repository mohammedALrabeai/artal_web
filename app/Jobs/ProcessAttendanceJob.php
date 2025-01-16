<?php
namespace App\Jobs;

use Carbon\Carbon;
use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * تنفيذ المهمة.
     */
    public function handle()
    {
        $otpService = new OtpService();
        $phone = '966571718153';
        $time = Carbon::now()->toDateTimeString();

        try {
            // استدعاء الدوال اللازمة
            // \App\Services\AttendanceService::processAbsences();
            // \App\Services\LeaveService::storeLeaves();
            $message = "Attendance processing executed successfully at $time.";
            $otpService->sendOtp($phone, $message);
            Log::info($message);
        } catch (\Exception $e) {
            $errorMessage = 'Error processing attendance at ' . $time . ': ' . $e->getMessage();
            \Log::error($errorMessage);
            $otpService->sendOtp($phone, $errorMessage);
        }
    }
}
