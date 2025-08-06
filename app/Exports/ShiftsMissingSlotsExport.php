<?php
// app/Exports/ShiftsMissingSlotsExport.php
namespace App\Exports;

use App\Models\Shift;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class ShiftsMissingSlotsExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        // جلب الورديات مع عدّ الشواغر
        $shifts = Shift::with(['zone.project'])
            ->withCount('slots')
            ->get()
            ->filter(fn ($s) => $s->slots_count < $s->emp_no);   // النقص

        return $shifts->map(fn ($s) => [
            'المشروع'        => optional($s->zone?->project)->name,
            'الموقع'         => optional($s->zone)->name,
            'كود الوردية'    => $s->id,
            'اسم الوردية'    => $s->name,
            'المطلوب emp_no' => $s->emp_no,
            'عدد الشواغر'    => $s->slots_count,
            'النقص'          => $s->emp_no - $s->slots_count,
            'تاريخ البدء'    => $s->start_date,
        ]);
    }

    public function headings(): array
    {
        return [
            'المشروع',
            'الموقع',
            'كود الوردية',
            'اسم الوردية',
            'المطلوب (emp_no)',
            'عدد الشواغر',
            'النقص',
            'تاريخ البدء',
        ];
    }
}
