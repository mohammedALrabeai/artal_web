<?php

namespace App\Exports;

use App\Models\Leave;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LeavesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected ?string $from;
    protected ?string $to;

    public function __construct(?string $from, ?string $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee (Full Name)',
            'National ID',
            'Leave Type',
            'Approved',
            'Start Date',
            'End Date',
            'Total Days',
            'Project',
            'Zone',
            'Shift',
            'Reason',
            'Created At',
            'Updated At',
        ];
    }

    public function query()
    {
        $q = Leave::query()
            ->with([
                'employee',
                'leaveType',
                'employeeProjectRecord.project',
                'employeeProjectRecord.zone',
                'employeeProjectRecord.shift',
            ]);

        // فلترة حسب التاريخ:
        // - إذا تم إدخال (from & to): نستخدم تداخل الفترات (overlap).
        // - إذا تم إدخال (from فقط): إجازات تبدأ من هذا التاريخ فأعلى.
        // - إذا تم إدخال (to فقط): إجازات تنتهي حتى هذا التاريخ.
        if ($this->from && $this->to) {
            $q->whereDate('end_date', '>=', $this->from)
              ->whereDate('start_date', '<=', $this->to);
        } elseif ($this->from) {
            $q->whereDate('start_date', '>=', $this->from);
        } elseif ($this->to) {
            $q->whereDate('end_date', '<=', $this->to);
        }

        return $q->orderBy('start_date');
    }

    public function map($leave): array
    {
        $fullName = function ($emp) {
            return collect([
                $emp?->first_name,
                $emp?->father_name,
                $emp?->grandfather_name,
                $emp?->family_name,
            ])->filter()->join(' ') ?: '—';
        };

        $project = $leave->employeeProjectRecord?->project?->name ?? '—';
        $zone    = $leave->employeeProjectRecord?->zone?->name ?? '—';
        $shift   = $leave->employeeProjectRecord?->shift?->name ?? '—';

        $tz = 'Asia/Riyadh';

        $start = $leave->start_date ? Carbon::parse($leave->start_date) : null;
        $end   = $leave->end_date   ? Carbon::parse($leave->end_date)   : null;
        $days  = ($start && $end) ? $start->diffInDays($end) + 1 : null;

        return [
            $leave->id,
            $fullName($leave->employee),
            $leave->employee?->national_id ?? '—',
            $leave->leaveType?->name ?? '—',
            $leave->approved ? 'Yes' : 'No',
            optional($start)?->format('Y-m-d'),
            optional($end)?->format('Y-m-d'),
            $days,
            $project,
            $zone,
            $shift,
            $leave->reason ?? '',
            optional($leave->created_at)?->timezone($tz)?->format('Y-m-d H:i'),
            optional($leave->updated_at)?->timezone($tz)?->format('Y-m-d H:i'),
        ];
    }
}
