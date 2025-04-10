<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeProjectRecordsExport implements FromQuery, ShouldAutoSize, WithCustomCsvSettings, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $workPatternValues = [];

    public function query()
    {
        return EmployeeProjectRecord::query()
            ->with(['employee', 'project', 'zone', 'shift']); // ✅ جلب العلاقات المطلوبة لتسريع الاستعلام
    }

    public function headings(): array
    {
        $baseHeadings = [
            'الاسم الكامل', 'رقم الهوية', 'المشروع', 'الموقع', 'الوردية',
            'تاريخ البدء', 'تاريخ الانتهاء', 'الحالة',
        ];

        // توليد رؤوس التواريخ (30 يوم قادم)
        $dates = collect(range(0, 29))->map(function ($i) {
            return now('Asia/Riyadh')->copy()->addDays($i)->format('d M');
        });

        return array_merge($baseHeadings, $dates->toArray());
    }

    public function map($record): array
    {
        $fullName = trim(implode(' ', array_filter([
            $record->employee->first_name ?? '',
            $record->employee->father_name ?? '',
            $record->employee->grandfather_name ?? '',
            $record->employee->family_name ?? '',
        ])));

        $base = [
            $fullName,
            $record->employee->national_id ?? 'غير متوفر',
            $record->project->name ?? 'غير متوفر',
            $record->zone->name ?? 'غير متوفر',
            $record->shift->name ?? 'غير متوفر',
            $record->start_date,
            $record->end_date ?? 'غير محدد',
            $record->status ? 'نشط' : 'غير نشط',
        ];

        $workPattern = $this->getWorkPatternDays($record);
        $this->workPatternValues[] = $workPattern; // نحفظ القيم لاستعمالها في التنسيق

        return array_merge($base, $workPattern);

    }

    public function styles(Worksheet $sheet)
    {
        $startRow = 2; // الصف الأول يحتوي على العناوين
        $startCol = 9; // أول عمود لليوم (بعد 8 أعمدة رئيسية)

        foreach ($this->workPatternValues as $rowIndex => $days) {
            foreach ($days as $colOffset => $value) {
                $cell = $sheet->getCellByColumnAndRow($startCol + $colOffset, $startRow + $rowIndex);
                $style = $cell->getStyle();

                if ($value === '-') {
                    $style->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFC7CE'); // أحمر
                } else {
                    $style->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('C6EFCE'); // أخضر
                }
            }
        }

        return [];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
        ];
    }

    protected function getWorkPatternDays($record): array
    {
        if (! $record->shift || ! $record->shift->zone || ! $record->shift->zone->pattern) {
            return array_fill(0, 30, '❌');
        }

        $pattern = $record->shift->zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        $startDate = \Carbon\Carbon::parse($record->shift->start_date);
        $currentDate = \Carbon\Carbon::now('Asia/Riyadh');

        $days = [];

        for ($i = 0; $i < 30; $i++) {
            $targetDate = $currentDate->copy()->addDays($i);
            $totalDays = $startDate->diffInDays($targetDate);
            $currentDayInCycle = $totalDays % $cycleLength;
            $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

            $isWorkDay = $currentDayInCycle < $workingDays;
            $shiftType = '-';

            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';

                switch ($record->shift->type) {
                    case 'morning':
                        $shiftType = 'ص';
                        break;
                    case 'evening':
                        $shiftType = 'م';
                        break;
                    case 'evening_morning':
                        $shiftType = ($cycleNumber % 2 == 1) ? 'م' : 'ص';
                        break;
                }
            }

            $days[] = $shiftType;
        }

        return $days;
    }
}
