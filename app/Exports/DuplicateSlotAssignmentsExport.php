<?php 

// app/Exports/DuplicateSlotAssignmentsExport.php
namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class DuplicateSlotAssignmentsExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        // ① إيجاد الشواغر المكرَّرة
        $duplicateSlotIds = EmployeeProjectRecord::query()
            ->active()
            ->whereNotNull('shift_slot_id')
            ->groupBy('shift_slot_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('shift_slot_id');

        // ② جلب السجلات التفصيلية لتلك الشواغر
        return EmployeeProjectRecord::query()
            ->active()
            ->whereIn('shift_slot_id', $duplicateSlotIds)
            ->with(['employee','shift','zone','project','shiftSlot'])
            ->orderBy('shift_slot_id')
            ->get()
            ->map(fn($rec) => $this->mapRow($rec));
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /* ----------  خصائص وأدوات شائعة ---------- */
    protected array $headings = [
        'ID الشاغر','رقم الشاغر','الوردية (من الشاغر)',
        'اسم الموظف','رقم الهوية','المشروع','الموقع',
        'الوردية (في السجل)','تاريخ البداية','رقم السجل',
    ];

    protected function mapRow($rec): array
    {
        return [
            $rec->shift_slot_id,
            optional($rec->shiftSlot)->slot_number,
            optional($rec->shiftSlot?->shift)->name,
            optional($rec->employee)->name,
            optional($rec->employee)->national_id,
            optional($rec->project)->name,
            optional($rec->zone)->name,
            optional($rec->shift)->name,
            $rec->start_date,
            $rec->id,
        ];
    }
}
