<?php 

namespace App\Http\Controllers\attendance;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
            $headers[] = date('Y-m-d', $currentDate);
            $currentDate = strtotime('+1 day', $currentDate);
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
        ]);

        // إضافة بيانات الموظفين
        $rowIndex = 2;
        $sequence = 1; // تسلسل الأرقام
        foreach ($employees as $employee) {
            $startRow = $rowIndex; // بداية الصف للموظف
            $endRow = $rowIndex + 2; // نهاية الصف للموظف (ثلاثة صفوف)

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
            $sheet->setCellValue("I{$startRow}", 'COV');
            $sheet->setCellValue("I" . ($startRow + 1), 'HUR');
            $sheet->setCellValue("I" . ($startRow + 2), 'NOR');

            // إضافة بيانات الحضور لكل تاريخ
            $currentDate = strtotime($startDate);
            $columnIndex = 10; // التواريخ تبدأ بعد HRS
            while ($currentDate <= $endDateTimestamp) {
                $date = date('Y-m-d', $currentDate);

                $attendance = $employee->attendances->firstWhere('date', $date);
                $coverage = $attendance ? ($attendance->is_coverage ? 'COV' : '') : '';
                $workHours = $attendance ? $attendance->work_hours : '';
                $status = $attendance ? $attendance->status : 'N/A';

                // إدراج البيانات المكدسة
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $coverage);
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 1, $workHours);
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex + 2, $status);

                $currentDate = strtotime('+1 day', $currentDate);
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
