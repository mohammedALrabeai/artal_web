<?php 

// namespace App\Http\Controllers\attendance;

// use App\Models\Employee;
// use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;

// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\Style\Fill;

// class AttendanceExport2Controller extends Controller
// {
//     public function export2(Request $request)
//     {
//         $startDate = $request->query('start_date');
//         $endDate = $request->query('end_date');

//         // تحقق من صحة التواريخ
//         if (!$startDate || !$endDate) {
//             abort(400, 'Missing start_date or end_date');
//         }

//         // استرداد بيانات الموظفين
//         $employees = Employee::with([
//             'attendances' => function ($query) use ($startDate, $endDate) {
//                 $query->whereBetween('date', [$startDate, $endDate]);
//             },
//             'leaveBalances',
//             'currentZone',
//         ])->get();

//         // إنشاء ملف Excel
//         $spreadsheet = new Spreadsheet();
//         $sheet = $spreadsheet->getActiveSheet();

//         // إعداد الأعمدة الأساسية
//         $headers = [
//             'تسلسل', 
//             'الرقم الوظيفي',
//             'الاسم (Name)',
//             'رقم الهوية (I.D#)',
//             'رصيد الغياب',
//             'رصيد الإجازات المرضية',
//             'موقع العمل (UTILIZED PROJECT)',
//             'الراتب (Salary)',
//             'HRS',
//         ];

//         // إضافة التواريخ كأعمدة
//         $currentDate = strtotime($startDate);
//         $endDateTimestamp = strtotime($endDate);
//         while ($currentDate <= $endDateTimestamp) {
//             $headers[] = date('Y-m-d', $currentDate);
//             $currentDate = strtotime('+1 day', $currentDate);
//         }

//         // الأعمدة الإضافية بعد التواريخ
//         $columnsData = [
//             ['title' => 'أوفOFF', 'color' => 'BBDEFB', 'formula' => 'off'],
//             ['title' => 'عمل P', 'color' => 'C8E6C9', 'formula' => 'present'],
//             ['title' => 'إضافي COV', 'color' => 'FFD54F', 'formula' => 'coverage'],
//             ['title' => 'مرضي M', 'color' => 'FFCDD2', 'formula' => 'M'],
//             ['title' => 'إجازة مدفوعة PV', 'color' => '4CAF50', 'formula' => 'leave'],
//             ['title' => 'إجازة غير مدفوعة UV', 'color' => 'FFB74D', 'formula' => 'UV'],
//             ['title' => 'غياب A', 'color' => 'E57373', 'formula' => 'absent'],
//             ['title' => 'الإجمالي Total', 'color' => '90A4AE', 'formula' => ''],
//         ];

//         foreach ($columnsData as $column) {
//             $headers[] = $column['title'];
//         }

//         // كتابة الأعمدة في الصف الأول
//         $columnIndex = 1;
//         foreach ($headers as $header) {
//             $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
//             $columnIndex++;
//         }

//         // تنسيق الرؤوس
//         $highestColumn = $sheet->getHighestColumn();
//         $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
//             'fill' => [
//                 'fillType' => Fill::FILL_SOLID,
//                 'startColor' => ['rgb' => '4CAF50'],
//             ],
//             'font' => [
//                 'bold' => true,
//                 'color' => ['rgb' => 'FFFFFF'],
//             ],
//         ]);

//         // تحديد نطاق التواريخ
//         $startColumnIndex = 10; // العمود الأول للتواريخ بعد HRS
//         $endColumnIndex = $startColumnIndex + (strtotime($endDate) - strtotime($startDate)) / 86400;

//         $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumnIndex);
//         $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endColumnIndex);

//         // إضافة بيانات الموظفين
//         $rowIndex = 2;
//         $sequence = 1; // تسلسل الأرقام
//         foreach ($employees as $employee) {
//             $startRow = $rowIndex; // بداية الصف للموظف
//             $endRow = $rowIndex + 2; // نهاية الصف للموظف (ثلاثة صفوف)

//             // دمج الخلايا للأعمدة الأساسية
//             for ($col = 1; $col <= 9; $col++) {
//                 $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
//                 $sheet->mergeCells("{$cell}{$startRow}:{$cell}{$endRow}");
//             }

//             // تعبئة البيانات الأساسية
//             $sheet->setCellValue("A{$startRow}", $sequence++);
//             $sheet->setCellValue("B{$startRow}", $employee->employee_id);
//             $sheet->setCellValue("C{$startRow}", $employee->name());
//             $sheet->setCellValue("D{$startRow}", $employee->national_id);
//             $sheet->setCellValue("E{$startRow}", $employee->leaveBalances->where('leave_type', 'annual')->sum('balance'));
//             $sheet->setCellValue("F{$startRow}", $employee->leaveBalances->where('leave_type', 'sick')->sum('balance'));
//             $sheet->setCellValue("G{$startRow}", $employee->currentZone->name ?? 'غير محدد');
//             $sheet->setCellValue("H{$startRow}", $employee->basic_salary);

//             // إدخال العمود HRS
//             $sheet->setCellValue("I{$startRow}", 'COV');
//             $sheet->setCellValue("I" . ($startRow + 1), 'HUR');
//             $sheet->setCellValue("I" . ($startRow + 2), 'NOR');

//             // إدخال القيم في الأعمدة الإضافية
//             $extraColumnIndex = $endColumnIndex + 1;
//             foreach ($columnsData as $column) {
//                 $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($extraColumnIndex);
//                 $sheet->mergeCells("{$columnLetter}{$startRow}:{$columnLetter}{$endRow}");
                
//                 // صيغة العمود
//                 if ($column['formula']) {
//                     $formula = "=COUNTIF({$startColumn}{$startRow}:{$endColumn}{$endRow},\"{$column['formula']}\")";
//                     $sheet->setCellValue("{$columnLetter}{$startRow}", $formula);
//                 }

//                 // تنسيق العمود
//                 $sheet->getStyle("{$columnLetter}{$startRow}")->applyFromArray([
//                     'fill' => [
//                         'fillType' => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => $column['color']],
//                     ],
//                 ]);

//                 $extraColumnIndex++;
//             }

//             $rowIndex += 3; // الانتقال للموظف التالي
//         }

//         // إعداد تنزيل الملف
//         $fileName = "Attendance_Report_{$startDate}_to_{$endDate}.xlsx";
//         $tempFile = tempnam(sys_get_temp_dir(), 'attendance');
//         $writer = new Xlsx($spreadsheet);
//         $writer->save($tempFile);

//         return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
//     }
// }
