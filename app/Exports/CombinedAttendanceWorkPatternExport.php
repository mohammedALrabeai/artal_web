<?php

namespace App\Exports;

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '600');

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\EmployeeProjectRecord;

class CombinedAttendanceWorkPatternExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected int $numDaysInMonth;
    protected array $projectIds;
    protected array $attendanceColors;

    public function __construct(array $projectIds, ?string $date = null)
    {
        $this->projectIds = $projectIds;
        $this->startDate = $date ? Carbon::parse($date)->startOfMonth() : Carbon::now("Asia/Riyadh")->startOfMonth();
        $this->endDate = $this->startDate->copy()->endOfMonth();
        $this->numDaysInMonth = $this->startDate->daysInMonth;

        // ---==** التعديل 1: تعريف الحالات والألوان الجديدة **==---
        $this->attendanceColors = [
            'OFF' => 'FFC7CE', // أحمر فاتح للإجازة
            'N'   => '999999', // رمادي غامق
            'M'   => 'D9D9D9', // رمادي فاتح
            '❌' => 'FF0000', // أحمر صريح للخطأ
            '--'  => 'F0F0F0', // رمادي فاتح جدًا
            'PRE_ASSIGNMENT'  => 'C6E0B4', // أخضر فاتح
            'POST_ASSIGNMENT' => 'FF0000', // أحمر صريح
        ];
    }

    public function query()
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
                DB::raw('0 as is_missing_row'),
                'employees.id as employee_id',
                'employees.first_name', 'employees.father_name', 'employees.grandfather_name', 'employees.family_name',
                'employees.national_id',
                DB::raw('(IFNULL(employees.basic_salary, 0) + IFNULL(employees.living_allowance, 0) + IFNULL(employees.other_allowances, 0)) as total_salary'),
                DB::raw('IFNULL(annual_leaves.annual_leave_balance, 0) as annual_leave_balance'),
                DB::raw('IFNULL(sick_leaves.sick_leave_balance, 0) as sick_leave_balance'),
                'projects.name as project_name',
                'zones.id as zone_id', 'zones.name as zone_name',
                'shifts.id as shift_id', 'shifts.name as shift_name', 'shifts.start_date as shift_start_date', 'shifts.type as shift_type',
                'patterns.working_days', 'patterns.off_days',
                // ---==** التعديل 2: جلب تاريخي البدء والانتهاء **==---
                'employee_project_records.start_date as assignment_start_date',
                'employee_project_records.end_date as assignment_end_date'
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
                DB::raw('1 as is_missing_row'),
                DB::raw('NULL as employee_id'), DB::raw('NULL as first_name'), DB::raw('NULL as father_name'), DB::raw('NULL as grandfather_name'), DB::raw('NULL as family_name'),
                DB::raw('NULL as national_id'), DB::raw('NULL as total_salary'),
                DB::raw('NULL as annual_leave_balance'), DB::raw('NULL as sick_leave_balance'),
                'projects.name as project_name', 'zones.id as zone_id', 'zones.name as zone_name',
                'shifts.id as shift_id', 'shifts.name as shift_name', 'shifts.start_date as shift_start_date', 'shifts.type as shift_type',
                'patterns.working_days', 'patterns.off_days',
                DB::raw('NULL as assignment_start_date'),
                DB::raw('NULL as assignment_end_date')
            );

        return $employeesQuery->unionAll($missingShiftsQuery)
            ->orderBy('zone_id')->orderBy('shift_id')->orderBy('is_missing_row');
    }

    public function headings(): array
    {
        $base = ['تسلسل', 'الرقم الوظيفي', 'الاسم (Name)', 'رقم الهوية (I.D#)', 'رصيد الغياب', 'رصيد الإجازات المرضية', 'موقع العمل (UTILIZED PROJECT)', 'الراتب (Salary)', 'HRS'];
        $dates = [];
        for ($i = 0; $i < $this->numDaysInMonth; $i++) {
            $dates[] = "{$this->startDate->copy()->addDays($i)->format('Y-m-d')}\n{$this->startDate->copy()->addDays($i)->format('l')}";
        }
        $summary = ['أوفOFF', 'عمل M', 'عمل N', 'إجمالي Total', 'إجمالي الساعات'];
        return array_merge($base, $dates, $summary);
    }

    public function map($row): array
    {
        $workPattern = $this->getWorkPatternDays($row);

        if ($row->is_missing_row) {
            $baseData = ['', 'نقص', '-', '-', '-', '-', $row->project_name ?? '-', '-', 'NOR'];
        } else {
            $fullName = implode(' ', array_filter([$row->first_name, $row->father_name, $row->grandfather_name, $row->family_name]));
            $baseData = [
                '',
                $row->employee_id ?? '-',
                $fullName,
                $row->national_id ?? '-',
                $row->annual_leave_balance ?? 0,
                $row->sick_leave_balance ?? 0,
                $row->project_name ?? '-',
                $row->total_salary ?? '-',
                'NOR'
            ];
        }

        $summary = $this->calculateSummary($workPattern);
        $emptyDailyCells = array_fill(0, $this->numDaysInMonth, null);

        return [
            array_merge($baseData, $workPattern, $summary),
            array_merge(array_fill(0, 8, null), ['HUR'], $emptyDailyCells, array_fill(0, 5, null))
        ];
    }

    protected function calculateSummary(array $workPattern): array
    {
        $counts = array_count_values($workPattern);
        $totalHours = (($counts['M'] ?? 0) + ($counts['N'] ?? 0)) * 8;
        // ---==** التعديل 4: تحديث حساب الإجمالي **==---
        return [
            $counts['OFF'] ?? 0,
            $counts['M'] ?? 0,
            $counts['N'] ?? 0,
            count($workPattern) - ($counts['--'] ?? 0) - ($counts['❌'] ?? 0) - ($counts['PRE_ASSIGNMENT'] ?? 0) - ($counts['POST_ASSIGNMENT'] ?? 0),
            $totalHours
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
        $sheet->freezePane('J2');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestDataRow();
                $dailyColStart = 10;
                $dailyColEnd = $dailyColStart + $this->numDaysInMonth - 1;

                for ($i = 1; $i <= ($highestRow / 2); $i++) {
                    $currentRowNum = ($i * 2);
                    $sheet->setCellValue("A{$currentRowNum}", $i);
                    $isMissingRow = $sheet->getCell("B{$currentRowNum}")->getValue() === 'نقص';

                    for ($col = 'A'; $col <= 'I'; $col++) {
                        $sheet->mergeCells("{$col}{$currentRowNum}:{$col}" . ($currentRowNum + 1));
                    }

                    if ($isMissingRow) {
                        $sheet->getStyle("A{$currentRowNum}:I{$currentRowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
                        $sheet->getStyle("A{$currentRowNum}:I{$currentRowNum}")->getFont()->getColor()->setRGB('FFFFFF');
                    }

                    for ($colIndex = $dailyColStart; $colIndex <= $dailyColEnd; $colIndex++) {
                        $cellValue = $sheet->getCellByColumnAndRow($colIndex, $currentRowNum)->getValue();
                        $color = $this->attendanceColors[$cellValue] ?? 'FFFFFF';
                        $sheet->getStyleByColumnAndRow($colIndex, $currentRowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                    }
                }
            }
        ];
    }

    protected function getWorkPatternDays($record): array
    {
        $days = [];
        
        // ---==** التعديل 3: منطق رسم الأيام المحدث **==---
        $assignmentStartDate = !$record->is_missing_row && !empty($record->assignment_start_date)
            ? Carbon::parse($record->assignment_start_date)
            : Carbon::parse($record->shift_start_date);

        $assignmentEndDate = !$record->is_missing_row && !empty($record->assignment_end_date)
            ? Carbon::parse($record->assignment_end_date)
            : null;

        for ($i = 0; $i < $this->numDaysInMonth; $i++) {
            $targetDate = $this->startDate->copy()->addDays($i);

            // الحالة الأولى: اليوم قبل تاريخ بدء الإسناد
            if ($targetDate->isBefore($assignmentStartDate)) {
                $days[] = 'PRE_ASSIGNMENT';
                continue;
            }

            // الحالة الثانية: اليوم بعد تاريخ انتهاء الإسناد (إذا كان موجودًا)
            if ($assignmentEndDate && $targetDate->isAfter($assignmentEndDate)) {
                $days[] = 'POST_ASSIGNMENT';
                continue;
            }

            // الحالة الثالثة (الافتراضية): خلال فترة العمل
            if (empty($record->working_days) && empty($record->off_days)) {
                $days[] = '❌';
                continue;
            }

            $workingDays = (int) $record->working_days;
            $offDays = (int) $record->off_days;
            $cycleLength = $workingDays + $offDays;

            if ($cycleLength === 0) {
                $days[] = '❌';
                continue;
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

            $days[] = match ($shiftType) {
                'ص' => 'M', 'م' => 'N', '-' => 'OFF', default => '--',
            };
        }
        return $days;
    }
}
