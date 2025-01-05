<?php


namespace App\Exports;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;



class ProjectsZonesReportExport implements FromView, WithStyles
{
    public $startDate;
    public $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }



public function styles(Worksheet $sheet)
{
    // دمج الخلايا للتواريخ
    $datesCount = count($this->generateDates($this->startDate, $this->endDate));
    $startDateColumn = 'C'; // أول عمود بعد Project وZone
    $endDateColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($datesCount * 4)); // عمود النهاية

    // دمج الخلايا في الطبقة الأولى للتواريخ
    foreach (range(1, $datesCount) as $index) {
        $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(($index - 1) * 4 + 3);
        $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index * 4 + 2);
        $sheet->mergeCells("{$startColumn}1:{$endColumn}1");
    }

    // تنسيق النصوص في الرأس
    $sheet->getStyle("A1:{$endDateColumn}2")->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['rgb' => 'FFFFFF'], // النص أبيض
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4CAF50'], // لون الخلفية أخضر
        ],
    ]);

    // ضبط عرض الأعمدة
    foreach (range('A', $endDateColumn) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
}


    public function view(): View
    {
        $dates = $this->generateDates($this->startDate, $this->endDate);

        $projects = Project::with(['zones.attendances' => function ($query) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }])->get();

        return view('exports.projects_zones_report', [
            'projects' => $projects,
            'dates' => $dates,
        ]);
    }

    



    protected function generateDates($start, $end)
    {
        $dates = [];
        $current = Carbon::parse($start);
        $end = Carbon::parse($end);
    
        while ($current->lte($end)) {
            $date = $current->format('Y-m-d'); // التاريخ بصيغة YYYY-MM-DD
            $dayName = $current->translatedFormat('l'); // اسم اليوم باللغة العربية
            $dates[] = ['date' => $date, 'day' => $dayName];
            $current->addDay(); // الانتقال إلى اليوم التالي
        }
    
        return $dates;
    }
}
