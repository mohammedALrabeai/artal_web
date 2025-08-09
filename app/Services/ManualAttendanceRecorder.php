<?php

namespace App\Services;

use App\Models\EmployeeProjectRecord;
use App\Models\ManualAttendance;
use App\Models\ManualAttendanceEmployee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualAttendanceRecorder
{
    /**
     * يسجّل التحضير لموظف/إسناد في تاريخ معيّن.
     *
     * المدخلات:
     * - employee_project_record_id (int)   : إلزامي
     * - date (Y-m-d)                       : إلزامي
     * - status (string)                    : إلزامي (مثل M12, OFF, ...الخ)
     * - zone_id (int|null)                 : اختياري؛ لو لم يُمرّر => يستخدم zone الإسناد
     * - notes (string|null)                : اختياري
     * - has_coverage (bool=false)          : اختياري
     * - replaced_record_id (int|null)      : اختياري
     * - created_by (int|null)              : اختياري؛ لو لم يُمرّر يُؤخذ من Auth::id()
     *
     * الإرجاع:
     * [
     *   'mae_id' => int,
     *   'attendance_id' => int,
     *   'created_mae' => bool,
     *   'created_attendance' => bool,
     * ]
     */
    public function record(array $data): array
    {
        // 1) تحقق المدخلات الأساسية
        $eprId  = (int) ($data['employee_project_record_id'] ?? 0);
        $date   = $data['date'] ?? null;
        $status = $data['status'] ?? null;

        if ($eprId <= 0 || empty($date) || empty($status)) {
            throw ValidationException::withMessages([
                'input' => 'employee_project_record_id, date, status مطلوبة.',
            ]);
        }

        // 2) جلب الإسناد والتحقق من وجود zone
        /** @var EmployeeProjectRecord $epr */
        $epr = EmployeeProjectRecord::query()
            ->select(['id', 'zone_id'])
            ->findOrFail($eprId);

        $targetZoneId = $data['zone_id'] ?? $epr->zone_id; // لو لم يُمرّر => موقع الإسناد
        // dd($targetZoneId,($targetZoneId == $epr->zone_id));
        if (!$targetZoneId) {
            throw ValidationException::withMessages([
                'zone_id' => 'لا يمكن تحديد الموقع (zone_id).',
            ]);
        }

        $day   = Carbon::parse($date, 'Asia/Riyadh')->toDateString();
        $month = Carbon::parse($date, 'Asia/Riyadh')->startOfMonth()->toDateString();

        $notes        = $data['notes']             ?? null;
        $hasCoverage  = (bool)($data['has_coverage'] ?? false);
        $replacedId   = $data['replaced_record_id'] ?? null;
        $createdBy    = $data['created_by']         ?? Auth::id();

        return DB::transaction(function () use ($epr, $targetZoneId, $month, $day, $status, $notes, $hasCoverage, $replacedId, $createdBy) {

            // 3) ضمان وجود سجل MAE للشهر/الموقع
            //    إن كان نفس موقع الإسناد => is_main = true، خلافه false
            $isMain = ($targetZoneId == $epr->zone_id);

            /** @var ManualAttendanceEmployee $mae */
            $mae = ManualAttendanceEmployee::query()->firstOrCreate(
                [
                    'employee_project_record_id' => $epr->id,
                    'attendance_month'           => $month,
                    'actual_zone_id'             => $targetZoneId,
                ],
                [
                    'is_main' => $isMain, // سيتجاهله لو السجل موجود مسبقًا
                ]
            );

            // نحدد هل أنشأنا mae جديدًا الآن
            $createdMae = $mae->wasRecentlyCreated;

            // 4) تسجيل الحضور/تحديثه (مفتاح يومي: date + علاقة mae)
            /** @var ManualAttendance $attendance */
            $attendance = $mae->attendances()->updateOrCreate(
                ['date' => $day], // unique مع manual_attendance_employee_id على مستوى العلاقة
                [
                    'status'                            => $status,
                    'notes'                             => $notes,
                    'is_coverage'                       => $hasCoverage,
                    'replaced_employee_project_record_id' => $replacedId,
                    'created_by'                        => $createdBy,
                ]
            );

            return [
                'mae_id'             => $mae->id,
                'attendance_id'      => $attendance->id,
                'created_mae'        => $createdMae,
                'created_attendance' => $attendance->wasRecentlyCreated,
            ];
        });
    }
}
