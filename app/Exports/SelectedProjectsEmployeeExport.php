<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;


class SelectedProjectsEmployeeExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected Collection $records;

    protected array $workPatternValues = [];
    protected Carbon $startDate;

    public function __construct(array $projectIds, bool $onlyActive = true, ?string $startDate = null)
    {
        $query = EmployeeProjectRecord::with(['employee', 'project', 'zone', 'shift'])
            ->whereIn('project_id', $projectIds);

        if ($onlyActive) {
            $query->where('status', true);
        }

        $this->records = $query->get();
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::now('Asia/Riyadh');

    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        $baseHeadings = [
            'الاسم الكامل', 'رقم الهوية', 'المشروع', 'الموقع', 'الوردية',
            'تاريخ البدء', 'تاريخ الانتهاء', 'الحالة',
        ];

        $dates = collect(range(0, 30))->map(fn ($i) => $this->startDate->copy()->addDays($i)->format('d M'));
        return array_merge($baseHeadings, $dates->toArray());
    }

    public function map($record): array
    {
        $fullName = implode(' ', array_filter([
            $record->employee->first_name,
            $record->employee->father_name,
            $record->employee->grandfather_name,
            $record->employee->family_name,
        ]));

        $base = [
            $fullName,
            $record->employee->national_id,
            $record->project->name,
            $record->zone->name,
            $record->shift->name,
            $record->start_date,
            $record->end_date ?? 'غير محدد',
            $record->status ? 'نشط' : 'غير نشط',
        ];

        $workPattern = $this->getWorkPatternDays($record);
        $this->workPatternValues[] = $workPattern;

        return array_merge($base, $workPattern);

    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();

        // ✅ 1. تنسيق رؤوس الأعمدة
        $headerStyle = $sheet->getStyle("A1:{$highestCol}1");
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
        $headerStyle->getAlignment()->setHorizontal('center');

        // ✅ 2. محاذاة كل الجدول للوسط + حجم الخط
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getFont()->setSize(12);

        // ✅ 3. إضافة حدود بسيطة لكل الخلايا
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setRGB('DDDDDD');

        // ✅ 4. تلوين خلايا الأيام القادمة حسب الرمز
        $startRow = 2; // من بعد الهيدر
        $startCol = 9; // أول عمود لنمط العمل

        foreach ($this->workPatternValues as $rowIndex => $days) {
            foreach ($days as $colOffset => $value) {
                $cell = $sheet->getCellByColumnAndRow($startCol + $colOffset, $startRow + $rowIndex);
                $style = $cell->getStyle();

                $color = match ($value) {
                    'OFF' => 'FFC7CE', // أحمر
                    'N' => '999999', // رمادي غامق
                    'M' => 'D9D9D9', // رمادي فاتح
                    default => 'FFFFFF',
                };

                $style->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($color);
            }
        }
        $sheet->freezePane('B2');

        return [];
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
        // $currentDate = \Carbon\Carbon::now('Asia/Riyadh');

        $days = [];

        for ($i = 0; $i < 30; $i++) {
            $targetDate = $this->startDate->copy()->addDays($i);
            $totalDays = $startDate->diffInDays($targetDate);
            $currentDayInCycle = $totalDays % $cycleLength;
            $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

            $isWorkDay = $currentDayInCycle < $workingDays;
            $shiftType = '-';

            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';

                switch ($record->shift->type) {
                    case 'morning': $shiftType = 'ص';
                        break;
                    case 'evening': $shiftType = 'م';
                        break;
                    case 'evening_morning': $shiftType = ($cycleNumber % 2 == 1) ? 'م' : 'ص';
                        break;
                }
            }

            $days[] = match ($shiftType) {
                'ص' => 'M',
                'م' => 'N',
                '-' => 'OFF',
                default => '--',
            };

        }

        return $days;
    }
}
