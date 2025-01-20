<?php 

namespace App\Http\Controllers\attendance;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendanceExport2Controller extends Controller
{
    public function export2(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // تحقق من صحة التواريخ
        if (!$startDate || !$endDate) {
            abort(400, 'Missing start_date or end_date');
        }

        // استرداد بيانات الموظفين
        $employees = Employee::with([
            'attendances' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            },
            'leaveBalances',
            'currentZone',
        ])->get();

        // إنشاء ملف Excel
        $spreadsheet = new Spreadsheet();
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
            ['title' => 'الإجمالي Total', 'color' => '90A4AE', 'formula' => '=COUNTA($startColumn$row:$endColumn$row)'],
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
            $currentZone = $employee->currentZone ? $employee->currentZone->name : 'غير محدد';
            $salary = $employee->basic_salary + $employee->allowances; // تخصيص حساب الراتب حسب الحقول

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
            $sheet->setCellValue("B{$startRow}", $employee->employee_id); // الرقم الوظيفي
            $sheet->setCellValue("C{$startRow}", $employee->name()); // الاسم
            $sheet->setCellValue("D{$startRow}", $employee->national_id); // رقم الهوية
            $sheet->setCellValue("E{$startRow}", $annualLeaveBalance); // رصيد الغياب
            $sheet->setCellValue("F{$startRow}", $sickLeaveBalance); // رصيد الإجازات المرضية
            $sheet->setCellValue("G{$startRow}", $currentZone); // موقع العمل
            $sheet->setCellValue("H{$startRow}", $salary); // الراتب

            // إدخال العمود HRS
   
            $sheet->setCellValue("I{$startRow}", 'NOR');
            $sheet->setCellValue("I" . ($startRow + 1), 'HUR');
            $sheet->setCellValue("I" . ($startRow + 2), 'COV');

            // إضافة بيانات الحضور لكل تاريخ
            $currentDate = strtotime($startDate);
            $columnIndex = 10; // التواريخ تبدأ بعد HRS
            while ($currentDate <= $endDateTimestamp) {
                $date = date('Y-m-d', $currentDate);
               

                // جلب جميع سجلات الحضور لليوم
                $dailyAttendances = $employee->attendances->where('date', $date);
            
                // تحديد التغطية والحالة الأساسية وساعات العمل
                $coverage = $dailyAttendances->firstWhere('is_coverage', true); // تغطية اليوم
                $statusAttendance = $dailyAttendances->firstWhere('is_coverage', false); // الحضور الأساسي
            
                $coverageStatus = $coverage ? $coverage->status : ''; // حالة التغطية
                $status = $statusAttendance ? $statusAttendance->status : 'N/A'; // حالة الحضور الأساسي
                $workHours = $dailyAttendances->sum('work_hours'); // مجموع ساعات العمل

                // $attendance = $employee->attendances->firstWhere('date', $date);
                // $coverage = $attendance ? ($attendance->is_coverage ? 'COV' : '') : '';
                // $workHours = $attendance ? $attendance->work_hours : '';
                // $status = $attendance ? $attendance->status : 'N/A';
  // إدراج البيانات في الأعمدة
  $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $status);
  $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 1, $workHours);
  $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 2, $coverageStatus);

                // // إدراج البيانات المكدسة
                // $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex,$status );
                // $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 1, $workHours);
                // $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 2, $coverage);

                $currentDate = strtotime('+1 day', $currentDate);
                $columnIndex++;
            }

            // Adding formulas to the new columns
            $columnIndex = 10 + ($endDateTimestamp - strtotime($startDate)) / (60 * 60 * 24) + 1;
            foreach ($columnsData as $column) {
                $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(10); // Column 'J'
                $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex - 1);
                $formula = str_replace(['$startColumn', '$endColumn', '$row'], [$startColumn, $endColumn, $startRow], $column['formula']);
                $sheet->setCellValueByColumnAndRow($columnIndex, $startRow, $formula);
                $columnIndex++;
            }

            // الانتقال للموظف التالي
            $rowIndex += 3; // ثلاث صفوف لكل موظف
        }

        // إعداد تنزيل الملف
        $fileName = "Attendance_Report_{$startDate}_to_{$endDate}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'attendance');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
