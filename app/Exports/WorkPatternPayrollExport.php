<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use App\Models\Shift;
use App\Models\Zone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WorkPatternPayrollExport implements FromCollection, WithHeadings, WithStyles
{
    protected array $rows = [];
    protected array $workPatternValues = [];
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected string $monthName;

    public function __construct(protected array $projectIds)
    {
        $this->startDate = now()->startOfMonth();
        $this->endDate = now()->endOfMonth();
        $this->monthName = now()->translatedFormat('F Y');
    }

    public function collection(): Collection
    {
        $zones = Zone::with(['shifts.slots'])
            ->whereIn('project_id', $this->projectIds)
            ->get();

        $assignments = EmployeeProjectRecord::with(['employee', 'shiftSlot'])
            ->whereIn('project_id', $this->projectIds)
            ->where('status', true)
            ->whereDate('start_date', '<=', $this->endDate)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $this->startDate);
            })
            ->get();

        $sequence = 1;
        $period = $this->startDate->toPeriod($this->endDate);

        foreach ($zones as $zone) {
            foreach ($zone->shifts as $shift) {
                foreach ($shift->slots as $slot) {
                    $record = $assignments->first(fn($rec) =>
                        $rec->zone_id === $zone->id &&
                        $rec->shift_id === $shift->id &&
                        $rec->shift_slot_id === $slot->id &&
                        $rec->start_date <= $this->endDate &&
                        (is_null($rec->end_date) || $rec->end_date >= $this->startDate)
                    );

                    $employee = $record?->employee;
                    $rowBase = [
                        $sequence++,
                        $employee?->id ?? '-',
                        $employee?->name ?? 'نقص',
                        $employee?->national_id ?? '-',
                        $employee?->leaveBalances->where('leave_type', 'annual')->sum('balance') ?? '-',
                        $employee?->leaveBalances->where('leave_type', 'sick')->sum('balance') ?? '-',
                        $zone->project?->name ?? '-',
                        number_format($employee?->total_salary ?? 0, 2),
                    ];

                    $pattern = $this->getWorkPatternDays($shift, $record);
                    $this->workPatternValues[] = $pattern;

                    // الصف الأول: النمط
                    $this->rows[] = array_merge($rowBase, array_column($pattern, 'value'));
                    // الصف الثاني والثالث: فارغ
                    $this->rows[] = array_fill(0, count($rowBase) + count($pattern), '');
                    $this->rows[] = array_fill(0, count($rowBase) + count($pattern), '');
                }
            }
        }

        return collect($this->rows);
    }

    public function headings(): array
    {
        $base = ['#', 'الرقم الوظيفي', 'الاسم', 'رقم الهوية', 'رصيد الغياب', 'الإجازة المرضية', 'المشروع', 'الراتب'];
        $days = collect();
        $period = $this->startDate->toPeriod($this->endDate);
        foreach ($period as $date) {
            $days->push($date->format('d M'));
        }
        return array_merge($base, $days->toArray());
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestDataColumn();

        // دمج الأعمدة الثابتة كل 3 صفوف
        for ($row = 2; $row <= $highestRow; $row += 3) {
            foreach (range('A', 'H') as $col) {
                $sheet->mergeCells("{$col}{$row}:{$col}" . ($row + 2));
            }
        }

        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            'font' => ['size' => 12],
        ]);

        // تطبيق الألوان على نمط العمل
        foreach ($this->workPatternValues as $rowIndex => $days) {
            foreach ($days as $i => $day) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(9 + $i);
                $row = ($rowIndex * 3) + 2;
                if ($day['color']) {
                    $sheet->getStyle("{$col}{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $day['color']],
                        ],
                    ]);
                }
            }
        }

        return [];
    }

    protected function getWorkPatternDays(Shift $shift, $record): array
    {
        $pattern = $shift->zone?->pattern;
        if (! $pattern) return array_fill(0, $this->startDate->daysUntil($this->endDate)->count() + 1, ['value' => 'OFF', 'color' => 'FFC7CE']);

        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;
        $start = Carbon::parse($shift->start_date);

        $days = [];
        $period = $this->startDate->toPeriod($this->endDate);

        foreach ($period as $date) {
            $diff = $start->diffInDays($date);
            $dayInCycle = $diff % $cycleLength;
            $cycle = (int) floor($diff / $cycleLength) + 1;
            $isWorkDay = $dayInCycle < $workingDays;

            $shiftType = 'OFF';
            if ($isWorkDay) {
                $shiftType = match ($shift->type) {
                    'morning' => 'M',
                    'evening' => 'N',
                    'evening_morning' => $cycle % 2 === 1 ? 'N' : 'M',
                    'morning_evening' => $cycle % 2 === 1 ? 'M' : 'N',
                    default => 'M'
                };
            }

            $color = match($shiftType) {
                'OFF' => 'FFC7CE',
                'N' => '999999',
                'M' => 'D9D9D9',
                default => null
            };

            if ($record && $record->start_date && $date->lt(Carbon::parse($record->start_date))) {
                $color = 'FFF599'; // أصفر قبل البداية
            } elseif ($record && $record->end_date && $date->gt(Carbon::parse($record->end_date))) {
                $color = 'C00000'; // أحمر بعد النهاية
            }

            $days[] = ['value' => $shiftType, 'color' => $color];
        }

        return $days;
    }

    public function title(): string
    {
        return 'تقرير جدول التشغيل وتحضيرات الرواتب شهر ' . $this->monthName;
    }
}
