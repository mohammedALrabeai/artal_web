<?php

namespace App\Services;

use App\Models\AssetAssignment;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetTransferService
{
    /**
     * نقل العهدة من سجل مفتوح إلى موظف جديد في نفس اللحظة.
     *
     * @param  AssetAssignment  $openAssignment   السجل المفتوح الحالي (returned_date = null)
     * @param  int              $toEmployeeId     موظف الاستلام الجديد
     * @param  array            $options          [
     *                                              'assigned_date' => 'Y-m-d',
     *                                              'expected_return_date' => 'Y-m-d'|null,
     *                                              'condition_at_return' => string|null,
     *                                              'condition_at_assignment' => string|null,
     *                                              'notes' => string|null,
     *                                            ]
     * @return array [AssetAssignment $closedAssignment, AssetAssignment $newAssignment]
     * @throws ValidationException
     */
    public function transfer(AssetAssignment $openAssignment, int $toEmployeeId, array $options = []): array
    {
        if (! is_null($openAssignment->returned_date)) {
            throw ValidationException::withMessages([
                'record' => 'لا يمكن نقل تعيين مغلق.',
            ]);
        }

        $toEmployee = Employee::query()->find($toEmployeeId);
        if (! $toEmployee) {
            throw ValidationException::withMessages([
                'employee_id' => 'الموظف الجديد غير موجود.',
            ]);
        }

        if ($openAssignment->employee_id === $toEmployeeId) {
            throw ValidationException::withMessages([
                'employee_id' => 'لا يمكن نقل العهدة لنفس الموظف.',
            ]);
        }

        $now = now('Asia/Riyadh');
        $assignedDate = $options['assigned_date'] ?? $now->toDateString();
        $expectedReturnDate = $options['expected_return_date'] ?? null;

        return DB::transaction(function () use (
            $openAssignment,
            $toEmployeeId,
            $assignedDate,
            $expectedReturnDate,
            $options,
            $now
        ) {
            // 1) إغلاق السجل الحالي
            $openAssignment->update([
                'returned_date'        => $now->toDateString(),
                'returned_by_user_id'  => Auth::id(),
                'condition_at_return'  => $options['condition_at_return'] ?? $openAssignment->condition_at_return,
                'notes'                => $options['notes'] ?? $openAssignment->notes,
            ]);

            // 2) إنشاء سجل جديد للموظف المستلم
            $newAssignment = AssetAssignment::query()->create([
                'asset_id'               => $openAssignment->asset_id,
                'employee_id'            => $toEmployeeId,
                'assigned_date'          => $assignedDate,
                'expected_return_date'   => $expectedReturnDate,
                'condition_at_assignment'=> $options['condition_at_assignment'] ?? null,
                'notes'                  => $options['notes'] ?? null,
                'assigned_by_user_id'    => Auth::id(),
            ]);

            return [$openAssignment->fresh(), $newAssignment->fresh()];
        });
    }
}
