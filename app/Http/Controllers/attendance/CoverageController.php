<?php

namespace App\Http\Controllers\attendance;

use App\Enums\CoverageReason;
use App\Http\Controllers\Controller;
use App\Models\ApprovalFlow;
use App\Models\Attendance;
use App\Models\Coverage;
use App\Models\Employee;
use App\Models\Request as CoverageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CoverageController extends Controller
{
    /**
     * ✅ جلب قائمة أسباب التغطية والموظفين المتاحين في الموقع المحدد
     */
    public function getCoverageReasons(Request $request)
    {
        $zoneId = $request->zone_id;

        $availableEmployees = Employee::whereHas('currentZone', function ($query) use ($zoneId) {
            $query->where('zones.id', $zoneId);
        })
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => "{$employee->first_name} {$employee->father_name} {$employee->grandfather_name} {$employee->family_name} - ({$employee->national_id})"];
            });

        return response()->json([
            'success' => true,
            'coverage_reasons' => collect(CoverageReason::cases())->mapWithKeys(fn ($reason) => [
                $reason->value => [
                    'label' => __($reason->labels()[$reason->value]),
                    'requires_replacement' => $reason->requiresReplacement(),
                ],
            ]),
            'available_employees' => $availableEmployees,
        ]);

    }

    /**
     * ✅ الموافقة على التغطية (إنشاء سجل تغطية وطلب عند الحاجة)
     */
    public function approveCoverageRequest(Request $request, $attendance_id)
    {

        // 🔹 التحقق من المصادقة
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: No user authenticated. Please check your token.',
            ], 401);
        }
        // 🔹 جلب سجل الحضور
        $attendance = Attendance::findOrFail($attendance_id);

        // 🔹 التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'coverage_reason' => 'required|in:'.implode(',', array_keys(CoverageReason::labels())),
            'absent_employee_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 🔹 إنشاء سجل التغطية
        $coverage = Coverage::create([
            'employee_id' => $attendance->employee_id,
            'absent_employee_id' => $request->absent_employee_id,
            'zone_id' => $attendance->zone_id,
            'date' => $attendance->date,
            'status' => 'pending',
            'added_by' => Auth::id(),
            'reason' => $request->coverage_reason,
            'notes' => $request->notes,
        ]);

        // 🔹 تحديث جدول الحضور (`attendance`)
        $attendance->update([
            'approval_status' => 'submitted',
            'coverage_id' => $coverage->id,
        ]);

        // 🔹 جلب أول مستوى من سير الموافقات
        $approvalFlow = ApprovalFlow::where('request_type', 'coverage')->orderBy('approval_level', 'asc')->first();

        // 🔹 إذا كانت هناك موافقات مطلوبة، يتم إنشاء طلب جديد (`requests`)
        if ($approvalFlow) {
            CoverageRequest::create([
                'type' => 'coverage',
                'coverage_id' => $coverage->id,
                'submitted_by' => Auth::id(),
                'employee_id' => $attendance->employee_id,
                'current_approver_role' => $approvalFlow->approver_role,
                'description' => $request->notes,
                'additional_data' => json_encode([
                    'coverage_reason' => $request->coverage_reason,
                    'notes' => $request->notes,
                ]),
                'status' => 'pending',
            ]);
        } else {
            // إذا لم يكن هناك موافقات، يتم إكمال التغطية تلقائيًا
            $coverage->update(['status' => 'completed']);
            $attendance->update(['approval_status' => 'approved']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coverage approved successfully.',
            'data' => $coverage,
        ]);
    }

    /**
     * ✅ رفض التغطية (تحديث جدول الحضور فقط)
     */
    public function rejectCoverageRequest($attendance_id)
    {
        $attendance = Attendance::findOrFail($attendance_id);

        if ($attendance->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is not pending.'], 400);
        }

        // 🔹 تحديث جدول الحضور فقط (دون إنشاء طلب)
        $attendance->update(['approval_status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Coverage request rejected successfully.',
        ]);
    }
}
