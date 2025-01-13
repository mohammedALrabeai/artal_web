<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromView, WithEvents, WithStyles
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
        $employees = Employee::with([
            'attendances' => function ($query) {
                $query->whereBetween('date', [$this->startDate, $this->endDate]);
            },
            'leaveBalances', // لجلب رصيد الإجازات
            'currentZone', // لجلب موقع العمل الحالي
        ])->get();
    
        return view('exports.attendance', [
            'employees' => $employees,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
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

                // تلوين الصف الأول
                $sheet->getStyle('A1:Z1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4CAF50'], // خلفية خضراء
                    ],
                    'font' => [
                        'color' => ['rgb' => 'FFFFFF'], // لون النص أبيض
                    ],
                ]);

                // توسيع الأعمدة تلقائيًا
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // تمييز الحضور حسب الحالة
                $highestRow = $sheet->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {
                    for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                        $cellValue = $sheet->getCell("$column$row")->getValue();

                        if ($cellValue === 'present') {
                            $sheet->getStyle("$column$row")->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'C8E6C9'], // أخضر فاتح
                                ],
                            ]);
                        } elseif ($cellValue === 'absent') {
                            $sheet->getStyle("$column$row")->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFCDD2'], // أحمر فاتح
                                ],
                            ]);
                        } elseif ($cellValue === 'coverage') {
                            $sheet->getStyle("$column$row")->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'BBDEFB'], // أزرق فاتح
                                ],
                            ]);
                        } elseif ($cellValue === 'leave') {
                            $sheet->getStyle("$column$row")->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFF9C4'], // أصفر فاتح
                                ],
                            ]);
                        }

                    }
                    // Add formula to count "absent" statuses
                    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex + 1);
                    $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(11); // Column K
                    $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex);
                    $sheet->setCellValue("$lastColumn$row", "=COUNTIF($startColumn$row:$endColumn$row,\"absent\")");
                    $absentColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex + 1);
                    $coverageColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex + 2); // العمود الجديد

                    // Set background color of the new column cells to red
                    $sheet->getStyle("$lastColumn$row")->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFCDD2'], // أحمر فاتح
                        ],
                    ]);

                    // الغيابات
                    $sheet->setCellValue("$absentColumn$row", "=COUNTIF($startColumn$row:$endColumn$row,\"absent\")");
                    $sheet->getStyle("$absentColumn$row")->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFCDD2'], // أحمر فاتح
                        ],
                    ]);

                    // التغطيات
                    $sheet->setCellValue("$coverageColumn$row", "=COUNTIF($startColumn$row:$endColumn$row,\"coverage\")");
                    $sheet->getStyle("$coverageColumn$row")->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'BBDEFB'], // أزرق فاتح
                        ],
                    ]);
                }

                // Add header for the new column
                $sheet->setCellValue($lastColumn.'1', 'غياب');
                $sheet->getStyle($lastColumn.'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16, // Larger font size
                        'color' => ['rgb' => 'FFFFFF'], // لون النص أبيض
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFCDD2'], // أحمر فاتح
                    ],
                ]);

                // حساب الغيابات (absent)

                // رأس العمود للتغطيات
                $sheet->setCellValue($coverageColumn.'1', 'تغطية');
                $sheet->getStyle($coverageColumn.'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16, // Larger font size
                        'color' => ['rgb' => 'FFFFFF'], // لون النص أبيض
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BBDEFB'], // أزرق فاتح
                    ],
                ]);

 // توسيع الأعمدة تلقائيًا
 $highestColumn = $sheet->getHighestColumn();
 $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                $lastDataColumnIndex = $highestColumnIndex + 1;
$columnsData = [
    ['title' => 'أوفOFF', 'color' => 'BBDEFB', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"off\")"],
    ['title' => 'عمل P', 'color' => 'C8E6C9', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"present\")"],
    ['title' => 'إضافي COV', 'color' => 'FFD54F', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"coverage\")"],
    ['title' => 'مرضي M', 'color' => 'FFCDD2', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"M\")"],
    ['title' => 'إجازة مدفوعة PV', 'color' => '4CAF50', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"leave\")"],
    ['title' => 'إجازة غير مدفوعة UV', 'color' => 'FFB74D', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"UV\")"],
    ['title' => 'غياب A', 'color' => 'E57373', 'formula' => "=COUNTIF($startColumn$row:$endColumn$row,\"absent\")"],
    ['title' => 'الإجمالي Total', 'color' => '90A4AE', 'formula' => "=COUNTA($startColumn$row:$endColumn$row)"],
    ['title' => 'المخالفات الإدارية Infract', 'color' => 'FFE0B2', 'formula' => ''],
    ['title' => 'المخالفات المرورية', 'color' => 'FFE0B2', 'formula' => ''],
    ['title' => 'مكافأة', 'color' => 'FFE082', 'formula' => ''],
    ['title' => 'السلف adv', 'color' => 'FFCCBC', 'formula' => ''],
    ['title' => 'خصم التأمينات GOSI', 'color' => 'B39DDB', 'formula' => ''],
    ['title' => 'صافي الراتب Net salary', 'color' => 'B2FF59', 'formula' => ''],
    ['title' => 'إجمالي رصيد الغياب', 'color' => 'FFCDD2', 'formula' => ''],
    ['title' => 'إجمالي رصيد الإجازات المرضية', 'color' => 'FFCDD2', 'formula' => ''],
];

foreach ($columnsData as $key => $columnData) {
    $currentColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColumnIndex + $key);

    // تعيين رأس العمود
    $sheet->setCellValue("{$currentColumn}1", $columnData['title']);
    $sheet->getStyle("{$currentColumn}1")->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 14,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $columnData['color']],
        ],
    ]);

    // تعبئة البيانات
    for ($row = 2; $row <= $highestRow; $row++) {
        if (!empty($columnData['formula'])) {
            $sheet->setCellValue("{$currentColumn}{$row}", str_replace('$row', $row, $columnData['formula']));
        }
    }
}

            },
        ];
    }
}
