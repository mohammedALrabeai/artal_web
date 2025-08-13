<?php

namespace App\Exports;

use App\Models\AssetAssignment;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetAssignmentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected string $startDate,   // Y-m-d
        protected string $endDate,     // Y-m-d
        protected string $basis = 'both' // 'assigned' | 'returned' | 'both'
    ) {}

    public function headings(): array
    {
        return [
            'ID',
            'Asset ID',
            'Asset Name',
            'Serial Number',
            'Employee ID',
            'Employee Name',
            'National ID',
            'Assigned Date',
            'Expected Return Date',
            'Returned Date',
            'Condition at Assignment',
            'Condition at Return',
            'Assigned By',
            'Returned By',
            'Asset Status',
            'Notes',
            'Created At',
            'Updated At',
        ];
    }

    public function collection()
    {
        // نحسب حدود اليوم حسب الرياض
        $from = \Carbon\Carbon::parse($this->startDate, 'Asia/Riyadh')->startOfDay();
        $to   = \Carbon\Carbon::parse($this->endDate, 'Asia/Riyadh')->endOfDay();

        $q = AssetAssignment::query()
            ->with(['asset', 'employee', 'assignedBy', 'returnedBy']);

        // فلترة حسب الأساس المختار
        $q = match ($this->basis) {
            'assigned' => $q->whereBetween('assigned_date', [$from, $to]),
            'returned' => $q->whereBetween('returned_date', [$from, $to]),
            default    => $q->where(function (Builder $w) use ($from, $to) {
                $w->whereBetween('assigned_date', [$from, $to])
                  ->orWhereBetween('returned_date', [$from, $to]);
            }),
        };

        return $q->orderBy('assigned_date')->get();
    }

    public function map($r): array
    {
        $employeeName = $r->employee
            ? trim(implode(' ', array_filter([
                $r->employee->first_name,
                $r->employee->father_name,
                $r->employee->grandfather_name,
                $r->employee->family_name,
            ])))
            : null;

        return [
            $r->id,
            $r->asset_id,
            $r->asset?->asset_name,
            $r->asset?->serial_number,
            $r->employee_id,
            $employeeName,
            $r->employee?->national_id,
            optional($r->assigned_date)?->format('Y-m-d'),
            optional($r->expected_return_date)?->format('Y-m-d'),
            optional($r->returned_date)?->format('Y-m-d'),
            $r->condition_at_assignment,
            $r->condition_at_return,
            $r->assignedBy?->name ?? $r->assignedBy?->email,
            $r->returnedBy?->name ?? $r->returnedBy?->email,
            $r->asset?->status?->value,
            $r->notes,
            optional($r->created_at)?->format('Y-m-d H:i:s'),
            optional($r->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
