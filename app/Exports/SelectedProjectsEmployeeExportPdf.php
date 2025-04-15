<?php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Carbon\Carbon;
use Codedge\Fpdf\Fpdf\Fpdf;

class SelectedProjectsEmployeeExportPdf
{
    protected array $projectIds;

    protected bool $onlyActive;

    public function __construct(array $projectIds, bool $onlyActive = true)
    {
        $this->projectIds = $projectIds;
        $this->onlyActive = $onlyActive;
    }

    public function export(): string
    {
        $records = EmployeeProjectRecord::with(['employee', 'project', 'zone', 'shift'])
            ->whereIn('project_id', $this->projectIds)
            ->when($this->onlyActive, fn ($q) => $q->where('status', true))
            ->get();

        $pdf = new Fpdf('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, 'Employee Work Pattern Report', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 5, 'Generated on: '.now()->format('Y-m-d H:i'), 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 5.5);

        $baseHeaders = ['Full Name', 'National ID', 'Shift'];
        $dates = collect(range(0, 29))->map(fn ($i) => now()->addDays($i)->format('d M'))->toArray();
        $headers = array_merge($baseHeaders, $dates);

        $baseColWidths = [35, 30, 25];
        $dateColWidth = (287 - array_sum($baseColWidths)) / 30 - 0.3;

        // Header
        $pdf->SetFillColor(31, 78, 120);
        $pdf->SetTextColor(255);
        foreach ($headers as $i => $header) {
            $width = $i < 3 ? $baseColWidths[$i] : $dateColWidth;
            $pdf->Cell($width, 6, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0);

        foreach ($records as $record) {
            $fullName = implode(' ', array_filter([
                $record->employee->first_name,
                $record->employee->father_name,
                $record->employee->grandfather_name,
                $record->employee->family_name,
            ]));

            $row = [$fullName, $record->employee->national_id, $record->shift->name ?? '-'];
            $row = array_merge($row, $this->getWorkPatternDays($record));

            foreach ($row as $i => $value) {
                $width = $i < 3 ? $baseColWidths[$i] : $dateColWidth;

                if ($value === 'OFF') {
                    $pdf->SetFillColor(255, 199, 206);
                } elseif ($value === 'N') {
                    $pdf->SetFillColor(153, 153, 153);
                } elseif ($value === 'M') {
                    $pdf->SetFillColor(217, 217, 217);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                $pdf->Cell($width, 5, $value, 1, 0, 'C', true);
            }
            $pdf->Ln();
        }

        $pdfPath = storage_path('app/public/employee_assignment_report.pdf');
        $pdf->Output('F', $pdfPath);

        return $pdfPath;
    }

    protected function getWorkPatternDays($record): array
    {
        if (! $record->shift || ! $record->shift->zone || ! $record->shift->zone->pattern) {
            return array_fill(0, 30, '❌');
        }

        $pattern = $record->shift->zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        $startDate = Carbon::parse($record->shift->start_date);
        $currentDate = Carbon::now('Asia/Riyadh');

        $days = [];

        for ($i = 0; $i < 30; $i++) {
            $targetDate = $currentDate->copy()->addDays($i);
            $totalDays = $startDate->diffInDays($targetDate);
            $currentDayInCycle = $totalDays % $cycleLength;
            $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

            $isWorkDay = $currentDayInCycle < $workingDays;
            $shiftType = '-';

            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 === 1) ? 'M' : 'N';

                switch ($record->shift->type) {
                    case 'morning': $shiftType = 'M';
                        break;
                    case 'evening': $shiftType = 'N';
                        break;
                    case 'evening_morning': $shiftType = ($cycleNumber % 2 === 1) ? 'N' : 'M';
                        break;
                }
            }

            $days[] = match ($shiftType) {
                'M' => 'M',
                'N' => 'N',
                '-' => 'OFF',
                default => '--',
            };
        }

        return $days;
    }
}
