<?php

namespace App\Http\Controllers\attendance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceYearlyExportController extends Controller
{
    public function exportYearly(Request $request)
    {
        // التحقق من المدخلات: يجب تمرير معرّف الموظف والسنة المطلوبة
        $employeeId = $request->query('employee_id');
        $year = $request->query('year');

        if (! $employeeId || ! $year) {
            abort(400, 'Missing required parameters: employee_id or year');
        }

        // تحديد فترة العام المطلوب
        $startDate = $year.'-01-01';
        $endDate = $year.'-12-31';

        // جلب بيانات الموظف مع سجلات الحضور للسنة المحددة
        $employee = Employee::with(['attendances' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }])->findOrFail($employeeId);

        // إنشاء ملف Excel جديد
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // ********* قسم العنوان وبيانات الموظف *********
        // دمج خلايا العنوان ووضع عنوان التقرير
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'تقرير الحضور السنوي لموظف: '.$employee->name());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // تفاصيل الموظف (الرقم الوظيفي، الاسم والسنة)
        $sheet->setCellValue('A2', 'الرقم الوظيفي:');
        $sheet->setCellValue('B2', $employee->id);
        $sheet->setCellValue('A3', 'اسم الموظف:');
        $sheet->setCellValue('B3', $employee->name());
        $sheet->setCellValue('A4', 'السنة:');
        $sheet->setCellValue('B4', $year);

        // ترك صف فارغ قبل بداية الجدول
        $tableStartRow = 6;

        // ********* إعداد رؤوس الجدول *********
        // العمود الأول "الشهر"، ثم 31 عمود يمثل كل يوم، وبعدها أعمدة الإحصائيات
        $headers = [];
        $headers[] = 'الشهر';
        for ($day = 1; $day <= 31; $day++) {
            $headers[] = $day;
        }
        $summaryColumns = [
            'أوفOFF',
            'عمل P',
            'إضافي COV',
            'مرضي M',
            'إجازة مدفوعة PV',
            'إجازة غير مدفوعة UV',
            'غياب A',
            'انسحاب W',
            'الإجمالي Total',
            'إجمالي الساعات',
            'المخالفات الإدارية Infract',
            'المخالفات المرورية',
            'مكافأة',
            'السلف adv',
            'خصم التأمينات GOSI',
            'صافي الراتب Net salary',
            'إجمالي رصيد الغياب',
            'إجمالي رصيد الإجازات المرضية',
        ];
        foreach ($summaryColumns as $col) {
            $headers[] = $col;
        }

        // كتابة رؤوس الجدول في الصف $tableStartRow
        $colIndex = 1;
        foreach ($headers as $header) {
            $cellCoord = Coordinate::stringFromColumnIndex($colIndex).$tableStartRow;
            $sheet->setCellValue($cellCoord, $header);
            // تنسيق رؤوس الجدول: خلفية خضراء، نص أبيض، محاذاة مركزية وحدود رفيعة
            $sheet->getStyle($cellCoord)->applyFromArray([
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
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
            $colIndex++;
        }

        // ********* تعبئة بيانات الحضور لكل شهر *********
        // كل صف يمثل شهر من 1 إلى 12
        $currentRow = $tableStartRow + 1;
        // أسماء الشهور باللغة العربية
        $arabicMonths = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        // لكل شهر من السنة
        for ($month = 1; $month <= 12; $month++) {
            // وضع اسم الشهر في العمود الأول
            $sheet->setCellValueByColumnAndRow(1, $currentRow, $arabicMonths[$month]);
            $sheet->getStyle("A{$currentRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $colIndex = 2;
            $totalHours = 0; // لحساب مجموع ساعات العمل للشهر
            // لكل يوم من 1 إلى 31
            for ($day = 1; $day <= 31; $day++) {
                $cellCoord = Coordinate::stringFromColumnIndex($colIndex).$currentRow;
                if (checkdate($month, $day, $year)) {
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $attendance = $employee->attendances->firstWhere('date', $dateStr);
                    if ($attendance) {
                        $status = $attendance->status;
                        if ($status === 'present' && $attendance->check_in && ! $attendance->check_out) {
                            $status = 'W';
                        }
                        $hours = $attendance->work_hours;
                        $coverageStatus = $attendance->is_coverage ? $attendance->status : '';
                        $totalHours += $hours;
                        $cellValue = $status."\n".$hours."\n".$coverageStatus;
                        $sheet->setCellValue($cellCoord, $cellValue);
                    } else {
                        $sheet->setCellValue($cellCoord, '');
                    }
                } else {
                    $sheet->setCellValue($cellCoord, '');
                }
                // تنسيق خلايا الأيام: محاذاة مركزية، تغليف للنص وحدود
                $sheet->getStyle($cellCoord)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $colIndex++;
            }

            // ********* تعبئة أعمدة الإحصائيات *********
            // تبدأ أعمدة الإحصائيات من العمود رقم 33 (بعد عمود الشهر و 31 عمود يوم)
            $summaryStartCol = 33;
            $currentCol = $summaryStartCol;
            $startDayCol = 'B';
            $endDayCol = 'AF';
            $summaryFormulas = [
                'أوفOFF' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"off*")',
                'عمل P' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"present*")',
                'إضافي COV' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"coverage*")',
                'مرضي M' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"M*")',
                'إجازة مدفوعة PV' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"leave*")',
                'إجازة غير مدفوعة UV' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"UV*")',
                'غياب A' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"absent*")',
                'انسحاب W' => '=COUNTIF('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.',"W*")',
                'الإجمالي Total' => '=COUNTA('.$startDayCol.$currentRow.':'.$endDayCol.$currentRow.')',
                'إجمالي الساعات' => $totalHours,
                'المخالفات الإدارية Infract' => '',
                'المخالفات المرورية' => '',
                'مكافأة' => '',
                'السلف adv' => '',
                'خصم التأمينات GOSI' => '',
                'صافي الراتب Net salary' => '',
                'إجمالي رصيد الغياب' => '',
                'إجمالي رصيد الإجازات المرضية' => '',
            ];

            foreach ($summaryColumns as $colTitle) {
                $cellCoord = Coordinate::stringFromColumnIndex($currentCol).$currentRow;
                if (isset($summaryFormulas[$colTitle])) {
                    $value = $summaryFormulas[$colTitle];
                    $sheet->setCellValue($cellCoord, $value);
                } else {
                    $sheet->setCellValue($cellCoord, '');
                }
                // تنسيق خلايا الإحصائيات
                $sheet->getStyle($cellCoord)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $currentCol++;
            }

            $currentRow++;
        }

        // ضبط عرض الأعمدة تلقائيًا (استخدام دالة تحويل من النص إلى رقم)
        $highestColumnString = $sheet->getHighestColumn();
        $highestColumn = Coordinate::columnIndexFromString($highestColumnString);
        for ($col = 1; $col <= $highestColumn; $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        // تجميد الصفوف الخاصة بالرؤوس
        $sheet->freezePane('A'.($tableStartRow + 1));

        // إعداد تنزيل الملف
        $fileName = "Attendance_Yearly_Report_Employee_{$employee->id}_{$year}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'attendance_yearly');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
