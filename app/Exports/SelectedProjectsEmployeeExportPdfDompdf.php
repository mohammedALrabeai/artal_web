<?php

// app/Exports/SelectedProjectsEmployeeExportPdfDompdf.php

namespace App\Exports;

use App\Models\EmployeeProjectRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SelectedProjectsEmployeeExportPdfDompdf
{
    public static function download(array $projectIds, bool $onlyActive = true): StreamedResponse
    {
        $records = EmployeeProjectRecord::with(['employee', 'project', 'zone', 'shift'])
            ->whereIn('project_id', $projectIds);

        if ($onlyActive) {
            $records->where('status', true);
        }

        $dates = collect(range(0, 29))->map(fn ($i) => now('Asia/Riyadh')->addDays($i));

        $pdf = Pdf::loadView('exports.project-employee-pdf', [
            'records' => $records->get(),
            'dates' => $dates,
        ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return response()->streamDownload(
            fn () => print ($pdf->stream()),
            'employee_assignment_report.pdf'
        );
    }
}
