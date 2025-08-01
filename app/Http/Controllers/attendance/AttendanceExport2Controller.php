<?php

namespace App\Http\Controllers\attendance;

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300); // أو حتى 600 حسب الحاجة

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceExport2Controller extends Controller
{
    public function exportFiltered(Request $request)
    {
        // التحقق من المدخلات
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $employeeIds = explode(',', $request->query('employee_ids', ''));

        if (! $startDate || ! $endDate || empty($employeeIds)) {
            abort(400, 'Missing required parameters');
        }

        // جلب الموظفين المحددين فقط
        $employees = Employee::whereIn('id', $employeeIds)
            ->with([
                'attendances' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                },
                'leaveBalances',
                'latestZone.project',
            ])
            ->get();

        // استخدام نفس الكود الموجود في `export2`، لكن مع الموظفين المحددين فقط
        return $this->exportAttendanceData($employees, $startDate, $endDate);
    }

    public function export2(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (! $startDate || ! $endDate) {
            abort(400, 'Missing start_date or end_date');
        }

        // جلب جميع الموظفين
        $employees = Employee::with([
            'attendances' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            },
            'leaveBalances',
            'latestZone.project',
        ])->get();

        return $this->exportAttendanceData($employees, $startDate, $endDate);
    }

    public function exportAttendanceData($employees, $startDate, $endDate)
    {
        // $locale = App::getLocale();  // مثلاً "ar" أو "en" حسب الإعداد
        // App::setLocale($locale);     // هذا يضمن أن الدالة __() تستخدم اللغة الصحيحة

        // ↙ فرز حسب معرف المشروع (latestZone->project->id)
        $employees = $employees
            ->sortBy(fn ($emp) =>
                // إذا كنت على PHP 8+
                $emp->latestZone?->project?->id
                // وإذا لم تجد المشروع، أرجع قيمة كبيرة تدفعهم لنهاية القائمة
                ?? PHP_INT_MAX
            )
            ->values();

        // خريطة الألوان لكل حالة
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

        // تحقق من صحة التواريخ
        if (! $startDate || ! $endDate) {
            abort(400, 'Missing start_date or end_date');
        }

        // إنشاء ملف Excel
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد رأس الجدول
        $headers = [
            'تسلسل', // العمود الأول
            'الرقم الوظيفي',
            'الاسم (Name)',
            'رقم الهوية (I.D#)',
            'رصيد الغياب',
            'رصيد الإجازات المرضية',
            'موقع العمل (UTILIZED PROJECT)',
            'الراتب (Salary)',
            'HRS', // إضافة العمود HRS
        ];

        $currentDate = strtotime($startDate);
        $endDateTimestamp = strtotime($endDate);

        while ($currentDate <= $endDateTimestamp) {
            $date = date('Y-m-d', $currentDate);
            $dayName = date('l', $currentDate); // Get the day name
            $headers[] = "{$date}\n{$dayName}";
            $currentDate = strtotime('+1 day', $currentDate);
        }

        // Adding new columns
        $columnsData = [
            ['title' => 'أوفOFF', 'color' => 'BBDEFB', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"off")'],
            ['title' => 'عمل P', 'color' => 'C8E6C9', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"present")'],
            ['title' => 'إضافي COV', 'color' => 'FFD54F', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"coverage")'],
            ['title' => 'مرضي M', 'color' => 'FFCDD2', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"M")'],
            ['title' => 'إجازة مدفوعة PV', 'color' => '4CAF50', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"leave")'],
            ['title' => 'إجازة غير مدفوعة UV', 'color' => 'FFB74D', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"UV")'],
            ['title' => 'غياب A', 'color' => 'E57373', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"absent")'],
            ['title' => 'انسحاب W', 'color' => 'FFA07A', 'formula' => '=COUNTIF($startColumn$row:$endColumn$row,"W")'], // **عمود الانسحاب الجديد**

            ['title' => 'الإجمالي Total', 'color' => '90A4AE', 'formula' => '=COUNTA($startColumn$row:$endColumn$row)'],
            ['title' => 'إجمالي الساعات', 'color' => 'B39DDB', 'formula' => '=SUM($startColumn$row:$endColumn$row)'], // العمود الجديد
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
        $sequence = 1; // تسلسل الأرقام
        $rowColors = ['F5F5F5', 'E0E0E0']; // Alternating row colors
        foreach ($employees as $employee) {
            $startRow = $rowIndex; // بداية الصف للموظف
            $endRow = $rowIndex + 2; // نهاية الصف للموظف (ثلاثة صفوف)
            $rowColor = $rowColors[($sequence - 1) % 2]; // Alternating color

            $annualLeaveBalance = $employee->leaveBalances->where('leave_type', 'annual')->sum('balance');
            $sickLeaveBalance = $employee->leaveBalances->where('leave_type', 'sick')->sum('balance');
            // $currentZone = $employee->currentZone ? $employee->currentZone->name : 'غير محدد';
            $currentProject = $employee->latestZone && $employee->latestZone->project
            ? $employee->latestZone->project->name
            : 'غير محدد';

            // $salary = $employee->basic_salary + $employee->allowances; // تخصيص حساب الراتب حسب الحقول
            $salary = $employee->total_salary; // يستخدم الـ accessor الجديد

            // دمج الخلايا لبيانات الموظف الأساسية
            $sheet->mergeCells("A{$startRow}:A{$endRow}");
            $sheet->mergeCells("B{$startRow}:B{$endRow}");
            $sheet->mergeCells("C{$startRow}:C{$endRow}");
            $sheet->mergeCells("D{$startRow}:D{$endRow}");
            $sheet->mergeCells("E{$startRow}:E{$endRow}");
            $sheet->mergeCells("F{$startRow}:F{$endRow}");
            $sheet->mergeCells("G{$startRow}:G{$endRow}");
            $sheet->mergeCells("H{$startRow}:H{$endRow}");

            // Apply row color and center alignment
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

            // دمج الخلايا للأعمدة الجديدة
            $columnIndex = 10 + ($endDateTimestamp - strtotime($startDate)) / (60 * 60 * 24) + 1;
            foreach ($columnsData as $column) {
                $sheet->mergeCellsByColumnAndRow($columnIndex, $startRow, $columnIndex, $endRow);
                $sheet->setCellValueByColumnAndRow($columnIndex, $startRow, $column['title']);
                $sheet->getStyleByColumnAndRow($columnIndex, $startRow)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $column['color']],
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
                $columnIndex++;
            }

            // تعبئة البيانات الأساسية
            $sheet->setCellValue("A{$startRow}", $sequence++); // تسلسل
            $sheet->setCellValue("B{$startRow}", $employee->id); // الرقم الوظيفي
            $sheet->setCellValue("C{$startRow}", $employee->name()); // الاسم
            $sheet->setCellValue("D{$startRow}", $employee->national_id); // رقم الهوية
            $sheet->setCellValue("E{$startRow}", $annualLeaveBalance); // رصيد الغياب
            $sheet->setCellValue("F{$startRow}", $sickLeaveBalance); // رصيد الإجازات المرضية
            $sheet->setCellValue("G{$startRow}", $currentProject); // موقع العمل
            $sheet->setCellValue("H{$startRow}", number_format($salary, 2)); // الراتب

            // إدخال العمود HRS

            $sheet->setCellValue("I{$startRow}", 'NOR');
            $sheet->setCellValue('I'.($startRow + 1), 'HUR');
            $sheet->setCellValue('I'.($startRow + 2), 'COV');

            // إضافة بيانات الحضور لكل تاريخ
            $currentDate = strtotime($startDate);
            $columnIndex = 10; // التواريخ تبدأ بعد HRS
            while ($currentDate <= $endDateTimestamp) {
                $date = date('Y-m-d', $currentDate);

                // جلب جميع سجلات الحضور لليوم
                $dailyAttendances = $employee->attendances->where('date', $date);

                // تحديد التغطية والحالة الأساسية وساعات العمل
                $coverage = $dailyAttendances->firstWhere('is_coverage', true); // للإشارة إلى التغطية في حال واحد
                $statusAttendance = $dailyAttendances->firstWhere('is_coverage', false); // الحضور الأساسي
                $coverageStatus = $coverage ? $coverage->status : '';
                $status = $statusAttendance ? $statusAttendance->status : 'N/A';
                $workHours = $dailyAttendances->sum('work_hours');

                // تحديث حالة "انسحاب" إذا كان مسجل دخول بدون خروج
                if ($statusAttendance && $statusAttendance->status === 'present' && $statusAttendance->check_in && ! $statusAttendance->check_out) {
                    $status = 'W';
                }

                // إدراج البيانات في الأعمدة
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $status);
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 1, $workHours);
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 2, $coverageStatus);

                // تطبيق الألوان على الخلية الخاصة بالحالة
                if (isset($attendanceColors[$status])) {
                    $color = $attendanceColors[$status];
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex).$rowIndex;
                    $sheet->getStyle($cellCoordinate)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color],
                        ],
                    ]);
                }
                // إذا كانت حالة الحضور "present" (أو "حضور")
                if ($statusAttendance && ($statusAttendance->status === 'present' || $statusAttendance->status === 'حضور')) {
                    // استخراج التفاصيل الخاصة بالحضور
                    $siteName = (isset($statusAttendance->zone) && isset($statusAttendance->zone->name)) ? $statusAttendance->zone->name : 'غير محدد';
                    $checkIn = $statusAttendance->check_in ?? 'غير محدد';
                    $checkOut = $statusAttendance->check_out ?? 'غير محدد';
                    $workHoursDetail = $statusAttendance->work_hours ?? '0';
                    $presentComment = "الموقع: {$siteName} - دخول: {$checkIn} - خروج: {$checkOut} - ساعات: {$workHoursDetail}";

                    // تحديد موقع الخلية المعنية في الصف الأول (التي تحتوي على الحالة)
                    $cellCoordinatePresent = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex).$rowIndex;

                    // إضافة التعليق إلى الخلية
                    $sheet->getComment($cellCoordinatePresent)->getText()->createTextRun($presentComment);
                }

                // هنا نضيف كود التعليق الخاص بالتغطيات المتعددة:
                $coverages = $dailyAttendances->filter(function ($attendance) {
                    return $attendance->is_coverage;
                });
                if ($coverages->isNotEmpty() && isset($attendanceColors['coverage'])) {
                    $color = $attendanceColors['coverage'];
                    $coverageCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex).($rowIndex + 2);

                    // تطبيق اللون على الخلية
                    $sheet->getStyle($coverageCellCoordinate)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color],
                        ],
                    ]);

                    // بناء نص التعليق مع تفاصيل التغطيات
                    $commentLines = [];
                    foreach ($coverages as $cov) {
                        // استخدام علاقة zone للحصول على اسم الموقع
                        $siteName = (isset($cov->zone) && isset($cov->zone->name)) ? $cov->zone->name : 'غير محدد';
                        $checkIn = $cov->check_in ?? 'غير محدد';
                        $checkOut = $cov->check_out ?? 'غير محدد';
                        $hours = $cov->work_hours ?? '0';
                        $commentLines[] = "الموقع: {$siteName} - دخول: {$checkIn} - خروج: {$checkOut} - ساعات: {$hours}";
                    }
                    $commentText = implode("\n", $commentLines);

                    // إضافة التعليق إلى الخلية
                    $sheet->getComment($coverageCellCoordinate)->getText()->createTextRun($commentText);
                }

                $currentDate = strtotime('+1 day', $currentDate);
                $columnIndex++;
            }

            // Adding formulas to the new columns
            $columnIndex = 10 + ($endDateTimestamp - strtotime($startDate)) / (60 * 60 * 24) + 1;
            foreach ($columnsData as $column) {
                $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(10); // Column 'J'
                $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 1);
                // $formula = str_replace(['$startColumn', '$endColumn', '$row'], [$startColumn, $endColumn, $startRow], $column['formula']);
                // استبدال الصيغة
                if ($column['title'] === 'إضافي COV') {
                    $thirdRow = $rowIndex + 2; // السطر الثالث لكل موظف
                    $formula = "=COUNTIF({$startColumn}{$thirdRow}:{$endColumn}{$thirdRow}, \"coverage\")";
                } elseif ($column['title'] === 'إجمالي الساعات') {
                    $nextRow = $rowIndex + 1; // احسب الصف الثاني خارج النص
                    $formula = "=SUM({$startColumn}{$nextRow}:{$endColumn}{$nextRow})";

                } elseif ($column['title'] === 'انسحاب W') {
                    $formula = "=COUNTIF({$startColumn}{$startRow}:{$endColumn}{$startRow}, \"W\")";
                } elseif ($column['title'] === 'الإجمالي Total') {
                    $offCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 7); // عمود أوف
                    $pCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 6);  // عمود P
                    $covCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 5); // عمود COV
                    $mCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 4);   // عمود M
                    $pvCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 3);  // عمود PV
                    $absentCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 1); // عمود الغياب

                    $formula = "=SUM({$offCell}{$startRow}, {$pCell}{$startRow}, {$covCell}{$startRow}, {$mCell}{$startRow}, {$pvCell}{$startRow}) - {$absentCell}{$startRow}";
                } else {
                    $formula = str_replace(['$startColumn', '$endColumn', '$row'], [$startColumn, $endColumn, $startRow], $column['formula']);
                }
                $sheet->setCellValueByColumnAndRow($columnIndex, $startRow, $formula);
                $columnIndex++;
            }

            // تكون قيمة $colIndex هي العمود التالي الفارغ؛ فنستخدمه لعمود التأمينات:
            $insuranceCol = $columnIndex;

            // تحديد نص التأمين:
            $hasInsurance = $employee->commercial_record_id !== null;
            $sheet->mergeCellsByColumnAndRow($insuranceCol, $startRow, $insuranceCol, $endRow);
            $sheet->setCellValueByColumnAndRow(
                $insuranceCol,
                $startRow,
                $hasInsurance ? 'تامينات' : 'بدون تامينات'
            );
            // (اختياري) تلوين العمود:
            $sheet->getStyleByColumnAndRow($insuranceCol, $startRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'AED581'], // أخضر فاتح مثلاً
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // الانتقال للموظف التالي
            $rowIndex += 3; // ثلاث صفوف لكل موظف
        }
        // $sheet->freezePane('A2');
        $sheet->freezePane('J2');

        // إعداد تنزيل الملف
        $fileName = "Attendance_Report_{$startDate}_to_{$endDate}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'attendance');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
