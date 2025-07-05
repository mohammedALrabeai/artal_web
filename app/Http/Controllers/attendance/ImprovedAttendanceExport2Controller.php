<?php

namespace App\Http\Controllers\attendance;

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class ImprovedAttendanceExport2Controller extends Controller
{
    public function exportFiltered(Request $request)
    {
        // التحقق من المدخلات
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $employeeIds = explode(',', $request->query('employee_ids', ''));

        if (!$startDate || !$endDate || empty($employeeIds)) {
            abort(400, 'Missing required parameters');
        }

        // جلب الموظفين المحددين الذين لديهم حضور خلال الفترة فقط
        $employeesWithAttendance = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('employee_id', $employeeIds)
            ->distinct()
            ->pluck('employee_id');

        $employees = Employee::whereIn('id', $employeesWithAttendance)
            ->with([
                'attendances' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                },
                'leaveBalances',
                'latestZone.project',
                'projectRecords' => function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $endDate)
                          ->where(function ($subQ) use ($startDate) {
                              $subQ->whereNull('end_date')
                                   ->orWhere('end_date', '>=', $startDate);
                          });
                    })->with(['zone', 'project']);
                }
            ])
            ->get();

        return $this->exportAttendanceData($employees, $startDate, $endDate);
    }

    public function export2(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            abort(400, 'Missing start_date or end_date');
        }

        // جلب الموظفين الذين لديهم حضور خلال الفترة فقط
        $employeesWithAttendance = Attendance::whereBetween('date', [$startDate, $endDate])
            ->distinct()
            ->pluck('employee_id');

        $employees = Employee::whereIn('id', $employeesWithAttendance)
            ->with([
                'attendances' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                },
                'leaveBalances',
                'latestZone.project',
                'projectRecords' => function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $endDate)
                          ->where(function ($subQ) use ($startDate) {
                              $subQ->whereNull('end_date')
                                   ->orWhere('end_date', '>=', $startDate);
                          });
                    })->with(['zone', 'project']);
                }
            ])
            ->get();

        return $this->exportAttendanceData($employees, $startDate, $endDate);
    }

    public function exportAttendanceData($employees, $startDate, $endDate)
    {
        // فرز حسب معرف المشروع
        $employees = $employees
            ->sortBy(fn ($emp) => $emp->latestZone?->project?->id ?? PHP_INT_MAX)
            ->values();

        // خريطة الألوان للحالات العادية
        $attendanceColors = [
            'absent' => 'E57373',   // أحمر
            'present' => 'C8E6C9',  // أخضر فاتح
            'coverage' => 'FFD54F', // أصفر
            'M' => 'FFCDD2',        // وردي فاتح
            'leave' => '4CAF50',    // أخضر داكن
            'UV' => 'FFB74D',       // برتقالي
            'W' => 'FFA07A',        // برتقالي محمر (انسحاب)
            'off' => 'BBDEFB',      // أزرق فاتح
        ];

        // ألوان خاصة للخلايا خارج فترة الإسناد
        $outsideAssignmentColors = [
            'no_assignment' => 'FFE5E5',      // أحمر فاتح جداً
            'before_assignment' => 'FFF3E0',  // برتقالي فاتح
            'after_assignment' => 'F3E5F5',   // بنفسجي فاتح
        ];

        // إنشاء ملف Excel
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد رأس الجدول
        $headers = [
            'تسلسل',
            'الرقم الوظيفي',
            'الاسم (Name)',
            'رقم الهوية (I.D#)',
            'رصيد الغياب',
            'رصيد الإجازات المرضية',
            'موقع العمل (UTILIZED PROJECT)',
            'الراتب (Salary)',
            'HRS',
        ];

        $currentDate = strtotime($startDate);
        $endDateTimestamp = strtotime($endDate);

        while ($currentDate <= $endDateTimestamp) {
            $date = date('Y-m-d', $currentDate);
            $dayName = date('l', $currentDate);
            $headers[] = "{$date}\n{$dayName}";
            $currentDate = strtotime('+1 day', $currentDate);
        }

        // أعمدة الإحصائيات
        $columnsData = [
            ['title' => 'أوفOFF', 'color' => 'BBDEFB', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"off")'],
            ['title' => 'عمل P', 'color' => 'C8E6C9', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"present")'],
            ['title' => 'إضافي COV', 'color' => 'FFD54F', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"coverage")'],
            ['title' => 'مرضي M', 'color' => 'FFCDD2', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"M")'],
            ['title' => 'إجازة مدفوعة PV', 'color' => '4CAF50', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"leave")'],
            ['title' => 'إجازة غير مدفوعة UV', 'color' => 'FFB74D', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"UV")'],
            ['title' => 'غياب A', 'color' => 'E57373', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"absent")'],
            ['title' => 'انسحاب W', 'color' => 'FFA07A', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"W")'],
            ['title' => 'خارج الإسناد', 'color' => 'FFE5E5', 'formula' => '=SUMPRODUCT(--(ISNUMBER(SEARCH("*",$startColumn$row:$endColumn$row))))'],
            ['title' => 'الإجمالي Total', 'color' => '90A4AE', 'formula' => '=COUNTA($startColumn$row:$endColumn$row)'],
            ['title' => 'إجمالي الساعات', 'color' => 'B39DDB', 'formula' => '=SUM($startColumn$row:$endColumn$row)'],
            ['title' => 'المخالفات الإدارية Infract', 'color' => 'FFE0B2', 'formula' => ''],
            ['title' => 'المخالفات المرورية', 'color' => 'FFE0B2', 'formula' => ''],
            ['title' => 'مكافأة', 'color' => 'FFE082', 'formula' => ''],
            ['title' => 'السلف adv', 'color' => 'FFCCBC', 'formula' => ''],
            ['title' => 'خصم التأمينات GOSI', 'color' => 'B39DDB', 'formula' => ''],
            ['title' => 'صافي الراتب Net salary', 'color' => 'B2FF59', 'formula' => ''],
            ['title' => 'إجمالي رصيد الغياب', 'color' => 'FFCDD2', 'formula' => ''],
            ['title' => 'إجمالي رصيد الإجازات المرضية', 'color' => 'FFCDD2', 'formula' => ''],
        ];

        foreach ($columnsData as $column) {
            $headers[] = $column['title'];
        }

        $headers[] = 'تامينات';

        // وضع الرؤوس في الصف الأول
        $columnIndex = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // تنسيق الرؤوس
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // إضافة بيانات الموظفين
        $rowIndex = 2;
        $sequence = 1;
        $rowColors = ['F5F5F5', 'E0E0E0'];

        foreach ($employees as $employee) {
            $startRow = $rowIndex;
            $endRow = $rowIndex + 2;
            $rowColor = $rowColors[($sequence - 1) % 2];

            $annualLeaveBalance = $employee->leaveBalances->where('leave_type', 'annual')->sum('balance');
            $sickLeaveBalance = $employee->leaveBalances->where('leave_type', 'sick')->sum('balance');
            $currentProject = $employee->latestZone && $employee->latestZone->project
                ? $employee->latestZone->project->name
                : 'غير محدد';
            $salary = $employee->total_salary;

            // دمج الخلايا لبيانات الموظف الأساسية
            $this->mergeCellsForEmployee($sheet, $startRow, $endRow);

            // تطبيق لون الصف والمحاذاة
            $sheet->getStyle("A{$startRow}:H{$endRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $rowColor],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // تعبئة البيانات الأساسية
            $this->fillEmployeeBasicData($sheet, $startRow, $sequence, $employee, $annualLeaveBalance, $sickLeaveBalance, $currentProject, $salary);

            // إضافة بيانات الحضور لكل تاريخ مع تحليل الإسناد
            $this->fillAttendanceDataWithAssignmentAnalysis($sheet, $employee, $startDate, $endDateTimestamp, $rowIndex, $attendanceColors, $outsideAssignmentColors);

            $rowIndex += 3;
            $sequence++;
        }

        // إنشاء وإرجاع ملف Excel
        return $this->generateExcelFile($spreadsheet, $startDate, $endDate);
    }

    /**
     * تحليل حالة الإسناد لتاريخ معين
     */
    private function getAssignmentStatus($employee, $date)
    {
        $checkDate = Carbon::parse($date);
        
        // البحث عن سجل إسناد نشط في هذا التاريخ
        $activeRecord = $employee->projectRecords->first(function ($record) use ($checkDate) {
            $startDate = Carbon::parse($record->start_date);
            $endDate = $record->end_date ? Carbon::parse($record->end_date) : null;
            
            return $checkDate->gte($startDate) && 
                   ($endDate === null || $checkDate->lte($endDate)) &&
                   $record->status == true;
        });

        if ($activeRecord) {
            return 'active';
        }

        // التحقق من وجود إسناد مستقبلي
        $futureRecord = $employee->projectRecords->first(function ($record) use ($checkDate) {
            $startDate = Carbon::parse($record->start_date);
            return $checkDate->lt($startDate) && $record->status == true;
        });

        if ($futureRecord) {
            return 'before_assignment';
        }

        // التحقق من وجود إسناد منتهي
        $pastRecord = $employee->projectRecords->first(function ($record) use ($checkDate) {
            $endDate = $record->end_date ? Carbon::parse($record->end_date) : null;
            return $endDate && $checkDate->gt($endDate);
        });

        if ($pastRecord) {
            return 'after_assignment';
        }

        return 'no_assignment';
    }

    /**
     * دمج الخلايا لبيانات الموظف الأساسية
     */
    private function mergeCellsForEmployee($sheet, $startRow, $endRow)
    {
        $sheet->mergeCells("A{$startRow}:A{$endRow}");
        $sheet->mergeCells("B{$startRow}:B{$endRow}");
        $sheet->mergeCells("C{$startRow}:C{$endRow}");
        $sheet->mergeCells("D{$startRow}:D{$endRow}");
        $sheet->mergeCells("E{$startRow}:E{$endRow}");
        $sheet->mergeCells("F{$startRow}:F{$endRow}");
        $sheet->mergeCells("G{$startRow}:G{$endRow}");
        $sheet->mergeCells("H{$startRow}:H{$endRow}");
    }

    /**
     * تعبئة البيانات الأساسية للموظف
     */
    private function fillEmployeeBasicData($sheet, $startRow, $sequence, $employee, $annualLeaveBalance, $sickLeaveBalance, $currentProject, $salary)
    {
        $sheet->setCellValue("A{$startRow}", $sequence);
        $sheet->setCellValue("B{$startRow}", $employee->id);
        $sheet->setCellValue("C{$startRow}", $employee->name());
        $sheet->setCellValue("D{$startRow}", $employee->national_id);
        $sheet->setCellValue("E{$startRow}", $annualLeaveBalance);
        $sheet->setCellValue("F{$startRow}", $sickLeaveBalance);
        $sheet->setCellValue("G{$startRow}", $currentProject);
        $sheet->setCellValue("H{$startRow}", number_format($salary, 2));

        $sheet->setCellValue("I{$startRow}", 'NOR');
        $sheet->setCellValue('I'.($startRow + 1), 'HUR');
        $sheet->setCellValue('I'.($startRow + 2), 'COV');
    }

    /**
     * تعبئة بيانات الحضور مع تحليل الإسناد
     */
    private function fillAttendanceDataWithAssignmentAnalysis($sheet, $employee, $startDate, $endDateTimestamp, $rowIndex, $attendanceColors, $outsideAssignmentColors)
    {
        $currentDate = strtotime($startDate);
        $columnIndex = 10; // التواريخ تبدأ بعد HRS

        while ($currentDate <= $endDateTimestamp) {
            $date = date('Y-m-d', $currentDate);
            
            // تحليل حالة الإسناد
            $assignmentStatus = $this->getAssignmentStatus($employee, $date);
            
            // جلب جميع سجلات الحضور لليوم
            $dailyAttendances = $employee->attendances->where('date', $date);

            // تحديد التغطية والحالة الأساسية وساعات العمل
            $coverage = $dailyAttendances->firstWhere('is_coverage', true);
            $statusAttendance = $dailyAttendances->firstWhere('is_coverage', false);
            $coverageStatus = $coverage ? $coverage->status : '';
            $status = $statusAttendance ? $statusAttendance->status : 'N/A';
            $workHours = $dailyAttendances->sum('work_hours');

            // تحديث حالة "انسحاب" إذا كان مسجل دخول بدون خروج
            if ($statusAttendance && $statusAttendance->status === 'present' && $statusAttendance->check_in && !$statusAttendance->check_out) {
                $status = 'W';
            }

            // تعديل النص إذا كان خارج فترة الإسناد
            $displayStatus = $status;
            $displayCoverageStatus = $coverageStatus;
            
            if ($assignmentStatus !== 'active') {
                $displayStatus = $status . '*';
                if ($coverageStatus) {
                    $displayCoverageStatus = $coverageStatus . '*';
                }
            }

            // إدراج البيانات في الأعمدة
            $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $displayStatus);
            $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 1, $workHours);
            $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 2, $displayCoverageStatus);

            // تطبيق الألوان
            $this->applyCellColoring($sheet, $columnIndex, $rowIndex, $status, $coverageStatus, $assignmentStatus, $attendanceColors, $outsideAssignmentColors);

            // إضافة التعليقات
            $this->addCellComments($sheet, $columnIndex, $rowIndex, $statusAttendance, $dailyAttendances);

            $currentDate = strtotime('+1 day', $currentDate);
            $columnIndex++;
        }
    }

    /**
     * تطبيق الألوان على الخلايا
     */
    private function applyCellColoring($sheet, $columnIndex, $rowIndex, $status, $coverageStatus, $assignmentStatus, $attendanceColors, $outsideAssignmentColors)
    {
        // تلوين خلية الحالة الأساسية
        if ($assignmentStatus !== 'active') {
            // خارج فترة الإسناد
            $color = $outsideAssignmentColors[$assignmentStatus] ?? $outsideAssignmentColors['no_assignment'];
        } else {
            // داخل فترة الإسناد
            $color = $attendanceColors[$status] ?? 'FFFFFF';
        }

        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex) . $rowIndex;
        $sheet->getStyle($cellCoordinate)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color],
            ],
        ]);

        // تلوين خلية التغطية إذا وجدت
        if ($coverageStatus) {
            $coverageColor = $assignmentStatus !== 'active' 
                ? $outsideAssignmentColors[$assignmentStatus] ?? $outsideAssignmentColors['no_assignment']
                : $attendanceColors['coverage'];
                
            $coverageCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex) . ($rowIndex + 2);
            $sheet->getStyle($coverageCellCoordinate)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $coverageColor],
                ],
            ]);
        }
    }

    /**
     * إضافة التعليقات للخلايا
     */
    private function addCellComments($sheet, $columnIndex, $rowIndex, $statusAttendance, $dailyAttendances)
    {
        // تعليق للحضور العادي
        if ($statusAttendance && ($statusAttendance->status === 'present' || $statusAttendance->status === 'حضور')) {
            $siteName = $statusAttendance->zone->name ?? 'غير محدد';
            $checkIn = $statusAttendance->check_in ?? 'غير محدد';
            $checkOut = $statusAttendance->check_out ?? 'غير محدد';
            $workHoursDetail = $statusAttendance->work_hours ?? '0';
            $presentComment = "الموقع: {$siteName} - دخول: {$checkIn} - خروج: {$checkOut} - ساعات: {$workHoursDetail}";

            $cellCoordinatePresent = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex) . $rowIndex;
            $sheet->getComment($cellCoordinatePresent)->getText()->createTextRun($presentComment);
        }

        // تعليق للتغطيات
        $coverages = $dailyAttendances->filter(function ($attendance) {
            return $attendance->is_coverage;
        });

        if ($coverages->isNotEmpty()) {
            $commentLines = [];
            foreach ($coverages as $cov) {
                $siteName = $cov->zone->name ?? 'غير محدد';
                $checkIn = $cov->check_in ?? 'غير محدد';
                $checkOut = $cov->check_out ?? 'غير محدد';
                $hours = $cov->work_hours ?? '0';
                $commentLines[] = "الموقع: {$siteName} - دخول: {$checkIn} - خروج: {$checkOut} - ساعات: {$hours}";
            }
            $commentText = implode("\n", $commentLines);

            $coverageCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex) . ($rowIndex + 2);
            $sheet->getComment($coverageCellCoordinate)->getText()->createTextRun($commentText);
        }
    }

    /**
     * إنشاء وإرجاع ملف Excel
     */
    private function generateExcelFile($spreadsheet, $startDate, $endDate)
    {
        $writer = new Xlsx($spreadsheet);
        $filename = "attendance_report_{$startDate}_to_{$endDate}.xlsx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

