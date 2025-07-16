<?php

namespace App\Exports;

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '600');

use Carbon\Carbon;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\EmployeeProjectRecord;

class CombinedAttendanceWorkPatternExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithEvents
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected int $numDaysInMonth;
    protected array $projectIds;
    protected array $attendanceColors;
    protected int $summaryColumnCount = 14;

    public function __construct(array $projectIds, ?string $date = null)
    {
        $this->projectIds = $projectIds;
        $this->startDate = $date ? Carbon::parse($date)->startOfMonth() : Carbon::now("Asia/Riyadh")->startOfMonth();
        $this->endDate = $this->startDate->copy()->endOfMonth();
        $this->numDaysInMonth = $this->startDate->daysInMonth;

        $this->attendanceColors = [
            'OFF' => 'FFC7CE', 'N' => '999999', 'M' => 'D9D9D9', '❌' => 'FF0000',
            '--' => 'F0F0F0', 'PRE_ASSIGNMENT' => 'C6E0B4', 'POST_ASSIGNMENT' => 'FF0000',
            'P' => 'D9D9D9', 'COV' => 'FFFF00', 'PV' => '00B0F0', 'UV' => '00B050', 'A' => 'FF0000', 'ERR' => 'FFC0CB'
        ];
    }

    /**
     * هذا هو الكود الناجح الذي يضمن استقرار العملية.
     * تم تعديله لوضع 'COV' تحت 'NOR'.
     */
    public function collection(): IlluminateCollection
    {
        $records = $this->getRecordsQuery()->get();
        $finalData = new IlluminateCollection();

        foreach ($records as $row) {
            $workPattern = $this->getWorkPatternDays($row);

            if ($row->is_missing_row) {
                $baseData = ['', 'نقص', '-', '-', '-', '-', $row->project_name ?? '-', '-', 'NOR'];
            } else {
                $fullName = implode(' ', array_filter([$row->first_name, $row->father_name, $row->grandfather_name, $row->family_name]));
                $baseData = [
                    '', $row->employee_id ?? '-', $fullName, $row->national_id ?? '-',
                    $row->annual_leave_balance ?? 0, $row->sick_leave_balance ?? 0,
                    $row->project_name ?? '-', $row->total_salary ?? 0, 'NOR'
                ];
            }

            $summaryPlaceholders = array_fill(0, $this->summaryColumnCount, null);
            $emptyDailyCells = array_fill(0, $this->numDaysInMonth, null);

            $firstRow = array_merge($baseData, $workPattern, $summaryPlaceholders);

            // ---==** التعديل النهائي: بناء الصف الثاني مع 'COV' في العمود التاسع **==---
            $secondRow = array_merge(
                array_fill(0, 8, null), // 8 خلايا فارغة للأعمدة A-H
                ['COV'],                 // القيمة 'COV' في العمود التاسع (I)
                $emptyDailyCells,        // خلايا فارغة لأعمدة الأيام
                array_fill(0, $this->summaryColumnCount, null) // خلايا فارغة لأعمدة الملخص
            );

            $finalData->push($firstRow);
            $finalData->push($secondRow);
        }

        return $finalData;
    }

    // ... (بقية الدوال تبقى كما هي بدون أي تغيير)

    protected function getRecordsQuery()
    {
        $annualLeaveSubQuery = DB::table('leave_balances')->select('employee_id', DB::raw('SUM(balance) as annual_leave_balance'))->where('leave_type', 'annual')->groupBy('employee_id');
        $sickLeaveSubQuery = DB::table('leave_balances')->select('employee_id', DB::raw('SUM(balance) as sick_leave_balance'))->where('leave_type', 'sick')->groupBy('employee_id');

        $employeesQuery = EmployeeProjectRecord::query()
            ->join('employees', 'employee_project_records.employee_id', '=', 'employees.id')
            ->join('projects', 'employee_project_records.project_id', '=', 'projects.id')
            ->join('zones', 'employee_project_records.zone_id', '=', 'zones.id')
            ->join('shifts', 'employee_project_records.shift_id', '=', 'shifts.id')
            ->leftJoin('patterns', 'zones.pattern_id', '=', 'patterns.id')
            ->leftJoinSub($annualLeaveSubQuery, 'annual_leaves', 'employees.id', '=', 'annual_leaves.employee_id')
            ->leftJoinSub($sickLeaveSubQuery, 'sick_leaves', 'employees.id', '=', 'sick_leaves.employee_id')
            ->whereIn('employee_project_records.project_id', $this->projectIds)
            ->where('employee_project_records.status', true)
            ->select(
                DB::raw('0 as is_missing_row'), 'employees.id as employee_id', 'employees.first_name', 'employees.father_name', 'employees.grandfather_name', 'employees.family_name',
                'employees.national_id', DB::raw('(IFNULL(employees.basic_salary, 0) + IFNULL(employees.living_allowance, 0) + IFNULL(employees.other_allowances, 0)) as total_salary'),
                DB::raw('IFNULL(annual_leaves.annual_leave_balance, 0) as annual_leave_balance'), DB::raw('IFNULL(sick_leaves.sick_leave_balance, 0) as sick_leave_balance'),
                'projects.name as project_name', 'zones.id as zone_id', 'zones.name as zone_name', 'shifts.id as shift_id', 'shifts.name as shift_name',
                'shifts.start_date as shift_start_date', 'shifts.type as shift_type', 'patterns.working_days', 'patterns.off_days',
                'employee_project_records.start_date as assignment_start_date', 'employee_project_records.end_date as assignment_end_date'
            );

        $assignedCountsSubQuery = DB::table('employee_project_records')->select('shift_id', DB::raw('count(*) as assigned_count'))->where('status', true)->groupBy('shift_id');

        $missingShiftsQuery = DB::table('shifts')
            ->join('zones', 'shifts.zone_id', '=', 'zones.id')
            ->join('projects', 'zones.project_id', '=', 'projects.id')
            ->leftJoin('patterns', 'zones.pattern_id', '=', 'patterns.id')
            ->leftJoinSub($assignedCountsSubQuery, 'counts', 'shifts.id', '=', 'counts.shift_id')
            ->whereIn('zones.project_id', $this->projectIds)
            ->where('shifts.status', true)
            ->whereRaw('shifts.emp_no > IFNULL(counts.assigned_count, 0)')
            ->select(
                DB::raw('1 as is_missing_row'), DB::raw('NULL as employee_id'), DB::raw('NULL as first_name'), DB::raw('NULL as father_name'), DB::raw('NULL as grandfather_name'), DB::raw('NULL as family_name'),
                DB::raw('NULL as national_id'), DB::raw('NULL as total_salary'), DB::raw('NULL as annual_leave_balance'), DB::raw('NULL as sick_leave_balance'),
                'projects.name as project_name', 'zones.id as zone_id', 'zones.name as zone_name', 'shifts.id as shift_id', 'shifts.name as shift_name',
                'shifts.start_date as shift_start_date', 'shifts.type as shift_type', 'patterns.working_days', 'patterns.off_days',
                DB::raw('NULL as assignment_start_date'), DB::raw('NULL as assignment_end_date')
            );

        return $employeesQuery->unionAll($missingShiftsQuery)->orderBy('zone_id')->orderBy('shift_id')->orderBy('is_missing_row');
    }

    public function headings(): array
    {
        $base = ['تسلسل', 'الرقم الوظيفي', 'الاسم (Name)', 'رقم الهوية (I.D#)', 'رصيد الغياب', 'رصيد الإجازات المرضية', 'موقع العمل (UTILIZED PROJECT)', 'الراتب (Salary)', 'HRS'];
        $dates = [];
        for ($i = 1; $i <= $this->numDaysInMonth; $i++) {
            $dates[] = $i;
        }
        $summary = [
            "أوف\nOFF", "عمل\nP", "إضافي\nCOV", "مرضي\nM", "إجازة مدفوعة\nPV", "إجازة غير مدفوعة\nUV", "غياب\nA",
            "الاجمالي\nTotal", "المخالفات الادارية\nInfract", "المخالفات المرورية", "مكافاة", "السلف\nadv", "خصم التأمينات\nGOSI", "صافي الراتب\nNet salary"
        ];
        return array_merge($base, $dates, $summary);
    }

    public function styles(Worksheet $sheet)
    {
        // Styling is handled in AfterSheet
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 5);
                $sheet->freezePane('A7');
                $headerStartRow = 6;
                $dataStartRow = $headerStartRow + 1;
                $totalColumnsLetter = $sheet->getHighestDataColumn();
                $highestRow = $sheet->getHighestDataRow();
                $dailyColStartIdx = 10;
                $dailyColEndIdx = $dailyColStartIdx + $this->numDaysInMonth - 1;
                $summaryColStartIdx = $dailyColEndIdx + 1;

                // ... (Header formatting remains the same)
                $sheet->mergeCells("A1:{$totalColumnsLetter}1")->setCellValue('A1', 'تحضيرات الموظفين المسجلين في التأمينات الاجتماعية');
                $sheet->mergeCells("A2:{$totalColumnsLetter}2")->setCellValue('A2', 'Time Sheet Employees registered with social insurance');
                $dateRange = sprintf('%s - %s of %s', $this->startDate->format('d'), $this->endDate->format('d'), $this->startDate->format('F Y'));
                $sheet->mergeCells("A3:{$totalColumnsLetter}3")->setCellValue('A3', $dateRange);
                $sheet->getStyle('A1:A3')->getFont()->setBold(true);
                $sheet->getStyle('A1:A2')->getFont()->setSize(14)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1:A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
                $sheet->getStyle('A3')->getFont()->setSize(12);
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $dayRow = 5;
                for ($i = 0; $i < $this->numDaysInMonth; $i++) {
                    $date = $this->startDate->copy()->addDays($i);
                    $sheet->setCellValueByColumnAndRow($dailyColStartIdx + $i, $dayRow, $date->format('D'));
                }
                $mainHeaderRange = "A{$headerStartRow}:{$totalColumnsLetter}{$headerStartRow}";
                $sheet->getStyle($mainHeaderRange)->getFont()->setBold(true);
                $sheet->getStyle($mainHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD6DCE4');

                // ... (The rest of the high-performance styling remains the same)
                for ($i = 1; $i <= (($highestRow - $headerStartRow) / 2); $i++) {
                    $currentRowNum = $headerStartRow + ($i * 2) - 1;
                    $sheet->setCellValue("A{$currentRowNum}", $i);
                    $daysRange = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dailyColStartIdx) . $currentRowNum . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dailyColEndIdx) . $currentRowNum;
                    $colMap = [];
                    $summaryCols = ["off", "p", "cov", "m", "pv", "uv", "a", "total", "infract", "traffic", "bonus", "adv", "gosi", "netSalary"];
                    foreach ($summaryCols as $key => $colName) {
                        $colMap[$colName] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($summaryColStartIdx + $key);
                    }
                    $sheet->setCellValue("{$colMap['off']}{$currentRowNum}", "=COUNTIF({$daysRange},\"OFF\")");
                    $sheet->setCellValue("{$colMap['p']}{$currentRowNum}", "=COUNTIF({$daysRange},\"P\")");
                    $sheet->setCellValue("{$colMap['cov']}{$currentRowNum}", "=COUNTIF({$daysRange},\"COV\")");
                    $sheet->setCellValue("{$colMap['m']}{$currentRowNum}", "=COUNTIF({$daysRange},\"M\")");
                    $sheet->setCellValue("{$colMap['pv']}{$currentRowNum}", "=COUNTIF({$daysRange},\"pv\")");
                    $sheet->setCellValue("{$colMap['uv']}{$currentRowNum}", "=COUNTIF({$daysRange},\"uv\")");
                    $sheet->setCellValue("{$colMap['a']}{$currentRowNum}", "=COUNTIF({$daysRange},\"A\")");
                    $sheet->setCellValue("{$colMap['total']}{$currentRowNum}", "=SUM({$colMap['p']}{$currentRowNum}:{$colMap['pv']}{$currentRowNum})-{$colMap['a']}{$currentRowNum}");
                    $sheet->setCellValue("{$colMap['netSalary']}{$currentRowNum}", "=IF({$colMap['total']}{$currentRowNum}>0, (I{$currentRowNum}/30*{$colMap['total']}{$currentRowNum}) - SUM({$colMap['infract']}{$currentRowNum},{$colMap['traffic']}{$currentRowNum},{$colMap['adv']}{$currentRowNum},{$colMap['gosi']}{$currentRowNum}) + {$colMap['bonus']}{$currentRowNum}, 0)");
                }
                $sheet->setShowGridlines(false);
                $summaryColors = [
                    'BDD7EE', 'DEEBF7', 'FFF2CC', 'E2EFDA', 'DDEBF7', 'C6E0B4', 'FFC7CE',
                    'FCE4D6', 'D9D9D9', 'D9D9D9', 'D9D9D9', 'D9D9D9', 'D9D9D9', 'A9D08E'
                ];
                for ($c = 0; $c < $this->summaryColumnCount; $c++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($summaryColStartIdx + $c);
                    $range = "{$colLetter}{$dataStartRow}:{$colLetter}{$highestRow}";
                    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($summaryColors[$c] ?? 'FFFFFF');
                }
                $fullTableRange = "A{$dataStartRow}:{$totalColumnsLetter}{$highestRow}";
                $styleArray = [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']], 'horizontal' => ['borderStyle' => Border::BORDER_NONE]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ];
                $sheet->getStyle($fullTableRange)->applyFromArray($styleArray);
                for ($i = $dataStartRow; $i <= $highestRow; $i += 2) {
                    $range = "A{$i}:{$totalColumnsLetter}{$i}";
                    $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFBFBFBF'));
                }
                $baseInfoRange = "A{$dataStartRow}:I{$highestRow}";
                $missingRowCondition = new Conditional();
                $missingRowCondition->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition('$' . 'B' . $dataStartRow . '="نقص"');
                $missingRowCondition->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
                $missingRowCondition->getStyle()->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($baseInfoRange)->setConditionalStyles([$missingRowCondition]);
                $dailyDataRange = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dailyColStartIdx) . $dataStartRow . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dailyColEndIdx) . $highestRow;
                $dayConditionalStyles = [];
                foreach ($this->attendanceColors as $value => $color) {
                    $condition = new Conditional();
                    $condition->setConditionType(Conditional::CONDITION_CELLIS)->setOperatorType(Conditional::OPERATOR_EQUAL)->addCondition('"' . $value . '"');
                    $condition->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                    $dayConditionalStyles[] = $condition;
                }
                $sheet->getStyle($dailyDataRange)->setConditionalStyles($dayConditionalStyles);
            }
        ];
    }

    protected function getWorkPatternDays($record): array
    {
        $days = [];
        try {
            if (empty($record->shift_start_date)) {
                return array_fill(0, $this->numDaysInMonth, '❌');
            }
            $assignmentStartDate = !$record->is_missing_row && !empty($record->assignment_start_date) ? Carbon::parse($record->assignment_start_date) : Carbon::parse($record->shift_start_date);
            $assignmentEndDate = !$record->is_missing_row && !empty($record->assignment_end_date) ? Carbon::parse($record->assignment_end_date) : null;

            for ($i = 0; $i < $this->numDaysInMonth; $i++) {
                $targetDate = $this->startDate->copy()->addDays($i);
                if ($targetDate->isBefore($assignmentStartDate)) {
                    $days[] = 'PRE_ASSIGNMENT'; continue;
                }
                if ($assignmentEndDate && $targetDate->isAfter($assignmentEndDate)) {
                    $days[] = 'POST_ASSIGNMENT'; continue;
                }
                if (empty($record->working_days) && empty($record->off_days)) {
                    $days[] = '❌'; continue;
                }
                $workingDays = (int) $record->working_days;
                $offDays = (int) $record->off_days;
                $cycleLength = $workingDays + $offDays;
                if ($cycleLength === 0) {
                    $days[] = '❌'; continue;
                }
                $shiftStartDate = Carbon::parse($record->shift_start_date);
                $totalDays = $shiftStartDate->diffInDays($targetDate, false);
                $currentDayInCycle = $totalDays % $cycleLength;
                $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;
                $isWorkDay = $currentDayInCycle < $workingDays;
                $shiftType = '-';
                if ($isWorkDay) {
                    switch ($record->shift_type) {
                        case 'morning': $shiftType = 'ص'; break;
                        case 'evening': $shiftType = 'م'; break;
                        case 'evening_morning': $shiftType = ($cycleNumber % 2 == 1) ? 'م' : 'ص'; break;
                        case 'morning_evening': $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م'; break;
                        default: $shiftType = 'ص'; break;
                    }
                }
                $days[] = match ($shiftType) { 'ص' => 'M', 'م' => 'N', '-' => 'OFF', default => '--' };
            }
        } catch (\Throwable $e) {
            // Log::error("Excel Export Error: " . $e->getMessage(), ['employee_id' => $record->employee_id ?? 'N/A']);
            return array_fill(0, $this->numDaysInMonth, 'ERR');
        }
        return $days;
    }
}
