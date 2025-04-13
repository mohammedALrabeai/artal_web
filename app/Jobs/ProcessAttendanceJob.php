<?php

namespace App\Jobs;

use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $stats;

    public function __construct(array $stats = [])
    {
        $this->stats = $stats;
    }

    /**
     * تنفيذ المهمة.
     */
    public function handle()
    {
        $otpService = new OtpService;
        $phone = '966571718153';
        $time = Carbon::now()->toDateTimeString();

        try {
            $absents = $this->stats['absent'] ?? 0;
            $offs = $this->stats['off'] ?? 0;

            $message = "✔️ تم تنفيذ معالجة الحضور بنجاح عند $time\n"
                ."📌 عدد الغياب: $absents\n"
                ."📌 عدد العطلات: $offs";

            $otpService->sendOtp($phone, $message);
            Log::info($message);

        } catch (\Exception $e) {
            $errorMessage = 'Error processing attendance at '.$time.': '.$e->getMessage();
            Log::error($errorMessage);
            $otpService->sendOtp($phone, $errorMessage);
        }
    }
}
