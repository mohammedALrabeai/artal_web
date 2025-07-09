<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EmployeeChangesExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected string $from;
    protected string $to;

    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function collection(): Collection
    {
        $records = EmployeeProjectRecord::with(['employee', 'project', 'zone', 'shift', 'shiftSlot'])
            ->whereBetween('created_at', [$this->from, $this->to])
            ->get();

        return $records->map(function ($record) {
            $previous = EmployeeProjectRecord::with('employee')
                ->where('shift_slot_id', $record->shift_slot_id)
                ->where('created_at', '<', $record->created_at)
                ->latest('created_at')
                ->first();

            return [
                $record->employee?->name,
                $record->employee?->national_id,
                $record->employee?->mobile_number,
                $record->created_at?->format('Y-m-d H:i'),
                $record->project?->name,
                $record->zone?->name,
                $record->shift?->name,
                $record->shiftSlot?->slot_number,
                $previous?->employee?->name,
                $previous?->end_date ? \Carbon\Carbon::parse($previous->end_date)->format('Y-m-d') : '-', // ✅ الجديد
            ];
        });
    }

    public function headings(): array
    {
        return [
            'اسم الموظف',
            'رقم الهوية',
            'رقم الجوال',
            'تاريخ التوظيف',
            'المشروع',
            'الموقع',
            'الوردية',
            'رقم الشاغر',
            'بديل عن',
            'تاريخ خروج البديل',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // نمط العنوان (السطر الأول)
            1 => [
                'font' => ['bold' => true, 'size' => 13],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // اتجاه الصفحة من اليمين لليسار
                $sheet->setRightToLeft(true);

                $highestRow = $sheet->getHighestRow();

                // تحديد حدود النطاق الكامل
                $sheet->getStyle("A1:J{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFCCCCCC'],
                        ],
                    ],
                ]);


                // تنسيق رأس الجدول (السطر الأول)
                $sheet->getStyle("A1:J1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F81BD'],
                    ],
                ]);


                // تحديد عرض الأعمدة يدويًا (A إلى I)
                $columnWidths = [
                    'A' => 25, // اسم الموظف
                    'B' => 20, // الهوية
                    'C' => 20, // الجوال
                    'D' => 20, // تاريخ التوظيف
                    'E' => 20, // المشروع
                    'F' => 20, // الموقع
                    'G' => 20, // الوردية
                    'H' => 15, // الشاغر
                    'I' => 25, // بدل من الموظف
                    'J' => 20,
                ];

                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
