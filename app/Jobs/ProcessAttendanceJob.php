<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\OtpService;
use Carbon\Carbon;

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
        } catch (\Exception $e) {
            $errorMessage = 'Error processing attendance at ' . $time . ': ' . $e->getMessage();
            Log::error($errorMessage);
            $otpService->sendOtp($phone, $errorMessage);
        }
    }
}
