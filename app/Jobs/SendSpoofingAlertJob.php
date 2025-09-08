<?php

namespace App\Jobs;

use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSpoofingAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $otpService = new OtpService();
        $now = Carbon::now()->toDateTimeString();

        $p        = $this->payload;
        $to       = $p['recipient'];
        $locale   = $p['locale'] ?? 'ar';

        $empId    = $p['employee_id'] ?? '—';
        $empName  = $p['employee_name'] ?? null;
        $empPhone = $p['employee_phone'] ?? '—';
        $natId    = $p['employee_national_id'] ?? '—';

        $decision = $p['decision'] ?? 'mockDetected';
        $spoofers = $p['detected_spoofers'] ?? [];
        $platform = $p['platform'] ?? '—';
        $osVer    = $p['os_version'] ?? '—';
        $appVer   = $p['app_version'] ?? '—';

        $emp = $empName ? "{$empName} ({$empId})" : $empId;

        $isMock   = $decision === 'mockDetected';
        $reasonAr = $isMock ? 'اكتشاف موقع مُحاكَى (Fake/Mock)' : 'العثور على تطبيقات تغيير موقع مثبتة';
        $reasonEn = $isMock ? 'Detected Fake/Mock Location'       : 'Detected installed Fake-GPS apps';

        $spoofersLineAr = !empty($spoofers) ? "الحِزم: ".implode(', ', $spoofers)."\n" : "";
        $spoofersLineEn = !empty($spoofers) ? "Packages: ".implode(', ', $spoofers)."\n" : "";

        if ($locale === 'en') {
            $title = "⚠️ Location spoofing alert";
            $body  = "Employee: {$emp}\n"
                   . "Phone: {$empPhone}\n"
                   . "National ID: {$natId}\n"
                   . "Reason: {$reasonEn}\n"
                   . $spoofersLineEn
                   . "Action: Please contact the employee and verify on-site presence.\n"
                   . "—\n"
                   . "Platform: {$platform}\n"
                   . "OS: {$osVer}\n"
                   . "App: {$appVer}\n"
                   . "Server time: {$now}";
        } else {
            $title = "⚠️ تنبيه تزييف موقع";
            $body  = "الموظف: {$emp}\n"
                   . "الجوال: {$empPhone}\n"
                   . "رقم الهوية: {$natId}\n"
                   . "السبب: {$reasonAr}\n"
                   . $spoofersLineAr
                   . "الإجراء: فضلاً التواصل مع الموظف والتأكد من تواجده بالموقع.\n"
                   . "—\n"
                   . "المنصّة: {$platform}\n"
                   . "النظام: {$osVer}\n"
                   . "التطبيق: {$appVer}\n"
                   . "وقت الخادم: {$now}";
        }

        $text = $title . "\n\n" . $body;

        try {
            $otpService->sendOtp($to, $text);
            Log::info("[SpoofingAlert] sent to {$to} | emp={$empId} | decision={$decision}");
        } catch (\Throwable $e) {
            Log::error("[SpoofingAlert] failed to {$to} | emp={$empId} | {$e->getMessage()}");
        }
    }
}
