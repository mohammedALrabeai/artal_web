<?php 

// app/Exports/MismatchedSlotAssignmentsExport.php
namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class MismatchedSlotAssignmentsExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return EmployeeProjectRecord::query()
            ->active()
            ->whereNotNull('shift_slot_id')
            ->whereHas('shiftSlot', fn($q) =>
                $q->whereColumn('shift_slots.shift_id', '!=', 'employee_project_records.shift_id')
            )
            ->with(['employee','shift','zone','project','shiftSlot'])
            ->get()
            ->map(fn($rec) => $this->mapRow($rec));
    }

    public function headings(): array
    {
        return $this->headings;
    }

    protected array $headings = [
        'ID الشاغر','رقم الشاغر','الوردية الصحيحة (من الشاغر)',
        'الوردية المسجلة بالسجل','اسم الموظف','رقم الهوية',
        'المشروع','الموقع','تاريخ البداية','رقم السجل',
    ];

    protected function mapRow($rec): array
    {
        return [
            $rec->shift_slot_id,
            optional($rec->shiftSlot)->slot_number,
            optional($rec->shiftSlot?->shift)->name,
            optional($rec->shift)->name,
            optional($rec->employee)->name,
            optional($rec->employee)->national_id,
            optional($rec->project)->name,
            optional($rec->zone)->name,
            $rec->start_date,
            $rec->id,
        ];
    }
}
