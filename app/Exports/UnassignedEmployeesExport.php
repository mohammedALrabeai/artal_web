<?php

// app/Exports/UnassignedEmployeesExport.php

// app/Exports/UnassignedEmployeesExport.php
namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class UnassignedEmployeesExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return EmployeeProjectRecord::query()
            ->active()                     // 🔎 scope موضَّح بالأسفل
            ->whereNull('shift_slot_id')   // لا يملك شاغر
            ->with(['employee', 'shift', 'zone', 'project'])
            ->get()
            ->map(fn($rec) => $this->mapRow($rec));
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /* ----------  خصائص وأدوات شائعة ---------- */
    protected array $headings = [
        'اسم الموظف','رقم الهوية','الجوال',
        'المشروع','الموقع','الوردية',
        'تاريخ البداية','رقم السجل','ID الشاغر',
    ];

    protected function mapRow($rec): array
    {
        return [
            optional($rec->employee)->name,
            optional($rec->employee)->national_id,
            optional($rec->employee)->mobile_number,
            optional($rec->project)->name,
            optional($rec->zone)->name,
            optional($rec->shift)->name,
            $rec->start_date,
            $rec->id,
            '—',            // لا يوجد شاغر
        ];
    }
}
