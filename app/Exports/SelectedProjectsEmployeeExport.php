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
protected array $missingShifts = [];

public function __construct(array $projectIds, bool $onlyActive = true, ?string $startDate = null)
{
    $query = EmployeeProjectRecord::with(['employee', 'project', 'zone', 'shift'])
        ->whereIn('project_id', $projectIds);

    if ($onlyActive) {
        $query->where('status', true);
    }

    $this->records = $query->get()->sortBy(fn($r) => $r->shift->id ?? 0);

    $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::now('Asia/Riyadh');

    // حساب النقص
    $grouped = $this->records->groupBy('shift_id');
    foreach ($grouped as $shiftId => $group) {
        $shift = $group->first()?->shift;
        if ($shift && $shift->emp_no > $group->count()) {
            $this->missingShifts[] = [
                'shift' => $shift,
                'missing_count' => $shift->emp_no - $group->count(),
            ];
        }
    }
}


 public function collection()
{
    $groupedByShift = $this->records->groupBy('shift_id');
    $orderedRows = collect();

    foreach ($groupedByShift as $shiftId => $group) {
        $shift = $group->first()?->shift;

        // ✅ أضف الموظفين الفعليين
        foreach ($group as $record) {
            $orderedRows->push($record);
        }

        // ✅ احسب النقص
        if ($shift && $shift->emp_no > $group->count()) {
            $missingCount = $shift->emp_no - $group->count();
            $project = $group->first()?->project;
            $zone = $group->first()?->zone;

            for ($i = 0; $i < $missingCount; $i++) {
                $orderedRows->push((object)[
                    'is_missing_row' => true,
                    'shift' => $shift,
                    'project' => $project,
                    'zone' => $zone,
                ]);
            }
        }
    }

    return $orderedRows;
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
    if (isset($record->is_missing_row)) {
        $base = [
            'نقص', // الاسم
            '-',   // الهوية
             $record->project->name ?? 'غير معروف', // ✅ اسم المشروع
            $record->zone->name ?? 'غير معروف',    // ✅ اسم الموقع
            $record->shift->name ?? 'بدون اسم',     // اسم الوردية
            '-',   // start_date
            '-',   // end_date
            '❌ نقص', // الحالة
        ];

       $workPattern = $this->getWorkPatternDays($record, );
      $this->workPatternValues[] = $workPattern;

    return array_merge($base, $workPattern);
    }

    // الحالة العادية:
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
        // ✅ 5. تلوين صفوف "نقص" كاملة
foreach ($sheet->getRowIterator(2) as $row) {
    $rowIndex = $row->getRowIndex();
    $cell = $sheet->getCell("A$rowIndex");

    if ($cell->getValue() === 'نقص') {
        // لون الاسم (A)
        $sheet->getStyle("A{$rowIndex}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF0000');
        $sheet->getStyle("A{$rowIndex}")->getFont()->getColor()->setRGB('FFFFFF');

        // رقم الهوية (B)
        $sheet->getStyle("B{$rowIndex}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF0000');

        // الوردية (E)
        $sheet->getStyle("E{$rowIndex}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF0000');
        $sheet->getStyle("E{$rowIndex}")->getFont()->getColor()->setRGB('FFFFFF');
    }
}



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
