<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithChunkReading;


class EmployeeProjectRecordsExport implements FromQuery, ShouldAutoSize, WithCustomCsvSettings, WithHeadings, WithMapping, WithStyles,WithChunkReading
{
    use Exportable;

    protected array $workPatternValues = [];

    protected $onlyActive;

    protected Carbon $startDate;

    /** @var array<int,int> project_id => count */
    // protected array $projectEmployeesCount = [];

    public function __construct(bool $onlyActive = true, ?string $startDate = null)
    {
        $this->onlyActive = $onlyActive;
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::now('Asia/Riyadh');

        // → استعلام واحد يجلب project_id و COUNT(*) لكل مشروع
        // $this->projectEmployeesCount = EmployeeProjectRecord::query()
        //     ->when($this->onlyActive, fn ($q) => $q->where('status', true))
        //     ->select('project_id', DB::raw('COUNT(*) as total'))
        //     ->groupBy('project_id')
        //     ->pluck('total', 'project_id')
        //     ->toArray();

    }

    public function query()
    {
        $query = EmployeeProjectRecord::query()
            ->with(['employee', 'project', 'zone', 'shift']);

        if ($this->onlyActive) {
            $query->where('status', true);
        }

        return $query;
    }

    public function headings(): array
    {
        $baseHeadings = [
            'الاسم الكامل', 'رقم الهوية', 'المشروع',
            'عدد الموظفين في المشروع',
            'الموقع', 'الوردية',
            'تاريخ البدء', 'تاريخ الانتهاء', 'الحالة',
        ];

        // توليد رؤوس التواريخ (30 يوم قادم)
        $dates = collect(range(0, 30))->map(fn ($i) => $this->startDate->copy()->addDays($i)->format('d M'));

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

        // نأخذ عدد الموظفين لهذا المشروع من الـ array المحسوب
        // $projectCount = $this->projectEmployeesCount[$record->project_id] ?? 0;

        $base = [
            $fullName,
            $record->employee->national_id ?? 'غير متوفر',
            $record->project->name ?? 'غير متوفر',
            $record->project->emp_no ?? '-', 
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
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();

        // ✅ 1. تنسيق رؤوس الأعمدة
        $headerStyle = $sheet->getStyle("A1:{$highestCol}1");
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
        $headerStyle->getAlignment()->setHorizontal('center');
        $headerStyle->getAlignment()->setVertical('center');

        // ✅ 2. توسيط وتنسيق كل الخلايا
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getFont()->setSize(12);

        // ✅ 3. إضافة حدود بسيطة لجميع الخلايا
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setRGB('DDDDDD');

        // ✅ 4. تلوين الأعمدة حسب نوع العمل
        $startRow = 2;
        $startCol = 9;

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

        // ✅ 5. تجميد أول 8 أعمدة (A → H)
        // $sheet->freezePane('I2');
        $sheet->freezePane('B2');

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
            return array_fill(0, 30, 'OFF');
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
            $shiftType = 'OFF';

            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'M' : 'N';

                switch ($record->shift->type) {
                    case 'morning':
                        $shiftType = 'M';
                        break;
                    case 'evening':
                        $shiftType = 'N';
                        break;
                    case 'evening_morning':
                        $shiftType = ($cycleNumber % 2 == 1) ? 'N' : 'M';
                        break;
                }
            }

            $days[] = $shiftType;
        }

        return $days;
    }


     public function chunkSize(): int
    {
        return 1000;
    }
}
