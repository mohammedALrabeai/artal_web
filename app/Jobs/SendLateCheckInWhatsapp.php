<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLateCheckInWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Employee $employee;
    public int $lateMinutes;

    public function __construct(Employee $employee, int $lateMinutes)
    {
        $this->employee = $employee;
        $this->lateMinutes = $lateMinutes;
    }

    public function handle(): void
    {
        try {
            $otpService = new OtpService();
            $groupId = '120363419460071587@g.us';

            $hours = floor($this->lateMinutes / 60);
            $minutes = $this->lateMinutes % 60;

            $delayText = '';
            if ($hours > 0) {
                $delayText = "{$hours} ساعة" . ($minutes > 0 ? " و {$minutes} دقيقة" : '');
            } else {
                $delayText = "{$minutes} دقيقة";
            }

            $message = "🚨 تأخير حضور\n"
                . "👤 الموظف: " . $this->employee->name() . "\n"
                . "📱 رقم الجوال: " . $this->employee->mobile_number . "\n"
                . "⏰ وقت التأخير: {$delayText}\n";

            $otpService->sendOtp($groupId, $message);
        } catch (\Throwable $e) {
            \Log::error('فشل إرسال رسالة واتساب بسبب التأخير', [
                'employee_id' => $this->employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
