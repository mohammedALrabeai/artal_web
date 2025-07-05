<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class EnhancedAttendanceExport implements FromView, WithEvents, WithStyles
{
    public $startDate;
    public $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        // جلب الموظفين الذين لديهم حضور خلال الفترة المختارة فقط
        $employeesWithAttendance = Attendance::whereBetween('date', [$this->startDate, $this->endDate])
            ->distinct()
            ->pluck('employee_id');

        $employees = Employee::with([
            'attendances' => function ($query) {
                $query->whereBetween('date', [$this->startDate, $this->endDate])
                      ->orderBy('date');
            },
            'leaveBalances',
            'currentZone',
            'projectRecords' => function ($query) {
                // جلب جميع سجلات الإسناد التي قد تتداخل مع الفترة
                $query->where(function ($q) {
                    $q->where('start_date', '<=', $this->endDate)
                      ->where(function ($subQ) {
                          $subQ->whereNull('end_date')
                               ->orWhere('end_date', '>=', $this->startDate);
                      });
                })->with(['zone', 'project', 'shift']);
            }
        ])
        ->whereIn('id', $employeesWithAttendance)
        ->orderBy('first_name')
        ->get();

        // إنشاء مصفوفة التواريخ للفترة المختارة
        $dates = [];
        $currentDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        
        while ($currentDate->lte($endDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // إعداد بيانات الحضور مع معلومات الإسناد المفصلة
        $employeeData = [];
        foreach ($employees as $employee) {
            $attendanceData = [];
            
            foreach ($dates as $date) {
                // البحث عن حضور في هذا التاريخ
                $attendance = $employee->attendances->where('date', $date)->first();
                
                // البحث عن سجل الإسناد النشط في هذا التاريخ
                $assignmentRecord = $this->getActiveAssignmentForDate($employee->projectRecords, $date);
                
                // تحديد حالة الحضور
                $status = $this->determineAttendanceStatus($attendance);
                
                // تحديد ما إذا كان التاريخ خارج فترة الإسناد
                $assignmentStatus = $this->getAssignmentStatus($employee->projectRecords, $date);
                
                $attendanceData[$date] = [
                    'status' => $status,
                    'assignment_status' => $assignmentStatus,
                    'attendance' => $attendance,
                    'assignment_record' => $assignmentRecord,
                    'zone_name' => $assignmentRecord ? $assignmentRecord->zone->name : null,
                    'project_name' => $assignmentRecord ? $assignmentRecord->project->name : null,
                ];
            }
            
            $employeeData[] = [
                'employee' => $employee,
                'attendance_data' => $attendanceData,
                'statistics' => $this->calculateEmployeeStatistics($attendanceData)
            ];
        }

        return view('exports.enhanced_attendance', [
            'employeeData' => $employeeData,
            'dates' => $dates,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'totalEmployees' => count($employeeData),
            'dateRange' => Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1,
        ]);
    }

    /**
     * تحديد حالة الحضور
     */
    private function determineAttendanceStatus($attendance)
    {
        if (!$attendance) {
            return 'absent';
        }

        if ($attendance->is_coverage) {
            return 'coverage';
        }

        // يمكن إضافة منطق للإجازات هنا
        if ($attendance->status === 'leave') {
            return 'leave';
        }

        if ($attendance->check_in) {
            return 'present';
        }

        return 'absent';
    }

    /**
     * الحصول على سجل الإسناد النشط في تاريخ معين
     */
    private function getActiveAssignmentForDate($projectRecords, $date)
    {
        $checkDate = Carbon::parse($date);
        
        return $projectRecords->first(function ($record) use ($checkDate) {
            $startDate = Carbon::parse($record->start_date);
            $endDate = $record->end_date ? Carbon::parse($record->end_date) : null;
            
            return $checkDate->gte($startDate) && 
                   ($endDate === null || $checkDate->lte($endDate)) &&
                   $record->status == true; // التأكد من أن السجل نشط
        });
    }

    /**
     * تحديد حالة الإسناد للتاريخ
     */
    private function getAssignmentStatus($projectRecords, $date)
    {
        $checkDate = Carbon::parse($date);
        $activeRecord = $this->getActiveAssignmentForDate($projectRecords, $date);
        
        if ($activeRecord) {
            return 'active'; // داخل فترة إسناد نشطة
        }

        // التحقق من وجود إسناد مستقبلي
        $futureRecord = $projectRecords->first(function ($record) use ($checkDate) {
            $startDate = Carbon::parse($record->start_date);
            return $checkDate->lt($startDate) && $record->status == true;
        });

        if ($futureRecord) {
            return 'before_assignment'; // قبل بداية الإسناد
        }

        // التحقق من وجود إسناد منتهي
        $pastRecord = $projectRecords->first(function ($record) use ($checkDate) {
            $endDate = $record->end_date ? Carbon::parse($record->end_date) : null;
            return $endDate && $checkDate->gt($endDate);
        });

        if ($pastRecord) {
            return 'after_assignment'; // بعد انتهاء الإسناد
        }

        return 'no_assignment'; // لا يوجد إسناد
    }

    /**
     * حساب إحصائيات الموظف
     */
    private function calculateEmployeeStatistics($attendanceData)
    {
        $stats = [
            'present' => 0,
            'absent' => 0,
            'coverage' => 0,
            'leave' => 0,
            'outside_assignment' => 0,
            'before_assignment' => 0,
            'after_assignment' => 0,
            'total_days' => count($attendanceData)
        ];

        foreach ($attendanceData as $dayData) {
            $status = $dayData['status'];
            $assignmentStatus = $dayData['assignment_status'];

            // عد حالات الحضور
            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            // عد حالات الإسناد
            if ($assignmentStatus !== 'active') {
                $stats['outside_assignment']++;
                
                if ($assignmentStatus === 'before_assignment') {
                    $stats['before_assignment']++;
                } elseif ($assignmentStatus === 'after_assignment') {
                    $stats['after_assignment']++;
                }
            }
        }

        return $stats;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]], // تنسيق الصف الأول
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // تلوين الصف الأول (رؤوس الأعمدة)
                $this->styleHeaderRow($sheet);

                // توسيع الأعمدة تلقائيًا
                $this->autoSizeColumns($sheet);

                // تطبيق التلوين على خلايا البيانات
                $this->applyCellColoring($sheet);

                // إضافة أعمدة الإحصائيات
                $this->addStatisticsColumns($sheet);
            },
        ];
    }

    private function styleHeaderRow($sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function autoSizeColumns($sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function applyCellColoring($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($row = 2; $row <= $highestRow; $row++) {
            for ($columnIndex = 11; $columnIndex <= $highestColumnIndex; $columnIndex++) { // بدءاً من عمود التواريخ
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                $cellValue = $sheet->getCell("$column$row")->getValue();
                
                $fillColor = $this->getCellColor($cellValue);
                
                if ($fillColor) {
                    $sheet->getStyle("$column$row")->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $fillColor],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                }
            }
        }
    }

    private function getCellColor($cellValue)
    {
        // ألوان للحالات خارج الإسناد
        if (strpos($cellValue, 'outside_') === 0 || strpos($cellValue, '*') !== false) {
            return 'FFE5E5'; // أحمر فاتح جداً
        }
        
        if (strpos($cellValue, 'before_') === 0) {
            return 'FFF3E0'; // برتقالي فاتح
        }
        
        if (strpos($cellValue, 'after_') === 0) {
            return 'F3E5F5'; // بنفسجي فاتح
        }

        // ألوان للحالات العادية
        switch ($cellValue) {
            case 'present':
            case 'ح':
                return 'C8E6C9'; // أخضر فاتح
            case 'absent':
            case 'غ':
                return 'FFCDD2'; // أحمر فاتح
            case 'coverage':
            case 'ت':
                return 'BBDEFB'; // أزرق فاتح
            case 'leave':
            case 'إ':
                return 'FFF9C4'; // أصفر فاتح
            default:
                return null;
        }
    }

    private function addStatisticsColumns($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(11); // عمود بداية التواريخ
        $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex);
        
        $statisticsColumns = [
            ['title' => 'أيام العمل', 'color' => 'C8E6C9', 'criteria' => 'ح'],
            ['title' => 'أيام الغياب', 'color' => 'FFCDD2', 'criteria' => 'غ'],
            ['title' => 'التغطيات', 'color' => 'BBDEFB', 'criteria' => 'ت'],
            ['title' => 'الإجازات', 'color' => 'FFF9C4', 'criteria' => 'إ'],
            ['title' => 'خارج الإسناد', 'color' => 'FFE5E5', 'criteria' => '*'],
            ['title' => 'الإجمالي', 'color' => '90A4AE', 'criteria' => null],
        ];

        foreach ($statisticsColumns as $index => $column) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex + 1 + $index);
            
            // إضافة رأس العمود
            $sheet->setCellValue("{$columnLetter}1", $column['title']);
            $sheet->getStyle("{$columnLetter}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $column['color']],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);

            // إضافة الصيغ للصفوف
            for ($row = 2; $row <= $highestRow; $row++) {
                if ($column['criteria']) {
                    if ($column['criteria'] === '*') {
                        // عد الخلايا التي تحتوي على *
                        $formula = "=SUMPRODUCT(--(ISNUMBER(SEARCH(\"*\",{$startColumn}{$row}:{$endColumn}{$row}))))";
                    } else {
                        // عد الخلايا التي تحتوي على الرمز المحدد
                        $formula = "=COUNTIF({$startColumn}{$row}:{$endColumn}{$row},\"{$column['criteria']}\")";
                    }
                } else {
                    // إجمالي الخلايا غير الفارغة
                    $formula = "=COUNTA({$startColumn}{$row}:{$endColumn}{$row})";
                }
                
                $sheet->setCellValue("{$columnLetter}{$row}", $formula);
                $sheet->getStyle("{$columnLetter}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $column['color']],
                    ],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
    }
}

