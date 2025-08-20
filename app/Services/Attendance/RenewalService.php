<?php

// app/Services/Attendance/RenewalService.php
namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceRenewal;
use App\Models\EmployeeStatus;
use Illuminate\Support\Facades\DB;

class RenewalService
{
    public function create(int $attendanceId, ?string $kind = 'manual', ?string $status = 'ok', ?array $payload = null): array
    {
        return DB::transaction(function () use ($attendanceId, $kind, $status, $payload) {
            /** @var Attendance $attendance */
            $attendance = Attendance::query()->findOrFail($attendanceId);

            /** @var AttendanceRenewal $renewal */
            $renewal = $attendance->renewals()->create([
                'renewed_at' => now(),
                'kind'       => $kind,
                'status'     => $status,
                'payload'    => $payload,
            ]);

            // تحديث last_present_at (حسب طلبك)
            if (isset($attendance->employee_id)) {
                EmployeeStatus::query()
                    ->where('employee_id', $attendance->employee_id)
                    ->update(['last_present_at' => now()]);
            }

            $expiresAt = $renewal->renewed_at->copy()->addMinutes(config('attendance.renewal_window_minutes', 30));

            return [
                'renewal_id'        => $renewal->id,
                'attendance_id'     => $attendance->id,
                'renewed_at'        => $renewal->renewed_at->toIso8601String(),
                'window_minutes'    => (int) config('attendance.renewal_window_minutes', 30),
                'expires_at'        => $expiresAt->toIso8601String(),
                'seconds_remaining' => max(0, $expiresAt->diffInSeconds(now(), false) * -1),
                'is_within_window'  => now()->lt($expiresAt),
            ];
        });
    }
}
