<?php


namespace App\Exports;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class AttendanceExport implements FromView, WithStyles, WithEvents
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
        $employees = Employee::with(['attendances' => function ($query) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }])->get();

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
                    for ($columnIndex = 8; $columnIndex <= $highestColumnIndex; $columnIndex++) {
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

                    // Set background color of the new column cells to red
                    $sheet->getStyle("$lastColumn$row")->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFCDD2'], // أحمر فاتح
                        ],
                    ]);
                }

                // Add header for the new column
                $sheet->setCellValue($lastColumn . '1', 'غياب');
                $sheet->getStyle($lastColumn . '1')->applyFromArray([
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
            },
        ];
    }
}
