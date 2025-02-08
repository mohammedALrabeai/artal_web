<?php

namespace App\Exports;

use App\Models\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RequestExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Request::with(['submittedBy', 'employee', 'approvals', 'leave'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Submitted By',
            'Employee',
            'Status',
            'Current Approver Role',
            'Duration',
            'Amount',
            'Description',
            'Approval Levels',
            'Leave Start Date',
            'Leave End Date',
            'Leave Type',
            'Leave Reason',
            'Created At',
        ];
    }

    public function map($request): array
    {
        return [
            $request->id,
            $request->type,
            $request->submittedBy->name ?? 'N/A',
            $request->employee->first_name.' '.$request->employee->family_name,
            $request->status,
            $request->current_approver_role,
            $request->duration ?? '-',
            $request->amount ?? '-',
            $request->description ?? '-',
            $request->approvalFlows->map(fn ($flow) => $flow->approver_role.' (Level '.$flow->approval_level.')')->implode(', '),
            $request->leave?->start_date ?? '-',
            $request->leave?->end_date ?? '-',
            $request->leave?->type ?? '-',
            $request->leave?->reason ?? '-',
            $request->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
