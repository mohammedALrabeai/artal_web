<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Jobs\SendSpoofingAlertJob;
use App\Models\Employee;

class AntiSpoofingAlertController extends Controller
{
    /**
     * اضبط المستقبل واللغة مباشرة هنا:
     * - المستقبل: رقم فردي بصيغة E.164 (مثال: 9665XXXXXXXX)
     *             أو معرف مجموعة واتساب (مثال: 120363419460071587@g.us)
     * - اللغة: 'ar' أو 'en'
     */
    // private const SPOOF_ALERT_RECIPIENT = '120363418395565157@g.us'; 
    private const SPOOF_ALERT_RECIPIENT = '966571718153';
    private const SPOOF_ALERT_LOCALE    = 'ar';

    public function store(Request $r)
    {
        // نبضة قصيرة من التطبيق
        $data = $r->validate([
            'decision'             => ['required', Rule::in(['mockDetected','spoofersDetected'])],
            'detected_spoofers'    => ['nullable','array'],
            'detected_spoofers.*'  => ['string','max:200'],
            'platform'             => ['nullable','string','max:40'],
            'os_version'           => ['nullable','string','max:160'],
            'app_version'          => ['nullable','string','max:40'],
        ]);

        // استخراج الموظف من الباك-إند عبر التوكن/الحارس
        $employee = $this->resolveEmployee($r);
        if (!$employee) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // اسم + جوال + هوية من Employee مباشرة
        $employeeId    = (string) $employee->id;
        $employeeName  = method_exists($employee, 'name') ? $employee->name() : null;
        $employeePhone = $employee->mobile_number ?: ($employee->phone_number ?: '—');
        $nationalId    = $employee->national_id ?: '—';

        $recipient = self::SPOOF_ALERT_RECIPIENT;
        $locale    = self::SPOOF_ALERT_LOCALE;

        if (empty($recipient)) {
            return response()->json(['message' => 'Recipient not configured in controller'], 500);
        }

        // حمولة المهمة
        $payload = [
            'employee_id'           => $employeeId,
            'employee_name'         => $employeeName,
            'employee_phone'        => $employeePhone,
            'employee_national_id'  => $nationalId,

            'decision'              => $data['decision'],
            'detected_spoofers'     => $data['detected_spoofers'] ?? [],
            'platform'              => $data['platform'] ?? null,
            'os_version'            => $data['os_version'] ?? null,
            'app_version'           => $data['app_version'] ?? null,

            'recipient'             => $recipient,
            'locale'                => $locale,
        ];

        SendSpoofingAlertJob::dispatch($payload);

        return response()->json(['status' => 'queued']);
    }

    /**
     * استخراج الموظف من الطلب بدون افتراضات:
     * - إذا كان الحارس يرجع Employee مباشرةً
     * - أو user لديه employee_id
     * - أو عبر Bearer Token/هيدر X-Employee-Token يطابق Employee.api_token
     * - أو بارام employee_id (للاختبارات)
     */
    private function resolveEmployee(Request $r): ?Employee
    {
        $user = $r->user();

        // 1) لو الحارس يعيد Employee مباشرة
        if ($user instanceof Employee) {
            return $user;
        }

        // 2) لو معرّف يوزر وفيه employee_id
        if ($user && isset($user->employee_id)) {
            $e = Employee::find($user->employee_id);
            if ($e) return $e;
        }

        // 3) جرّب التوكن (Bearer أو X-Employee-Token) مع حقل api_token في Employee
        $token = $r->bearerToken() ?: $r->header('X-Employee-Token');
        if (!empty($token)) {
            $e = Employee::where('api_token', $token)->first();
            if ($e) return $e;
        }

        // 4) (اختياري للاختبار فقط): بارام employee_id
        if ($r->filled('employee_id')) {
            $e = Employee::find($r->input('employee_id'));
            if ($e) return $e;
        }

        return null;
    }
}
