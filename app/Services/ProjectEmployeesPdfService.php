<?php


namespace App\Services;

use Carbon\Carbon;
use TCPDF;

class ProjectEmployeesPdfService
{
 public function generate($records, string $title = 'جدول التشغيل', ?string $startDate = null): void
{
    $start = $startDate ? Carbon::parse($startDate) : Carbon::now('Asia/Riyadh');
    $dates = collect(range(0, 30))->map(fn ($i) => $start->copy()->addDays($i));

    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetTitle($title);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->AddPage();
    $pdf->SetFont('aealarabiya', '', 10, '', true);

    // 🔹 تحميل جميع الورديات التابعة للمشاريع المختارة
    $zoneIds = $records->pluck('zone.id')->unique()->values();
    $allShifts = \App\Models\Shift::with(['zone', 'zone.pattern', 'zone.project'])
        ->whereIn('zone_id', $zoneIds)
        ->get();

    // 🔸 تجميع السجلات حسب الموقع ثم الورديات
    $groupedByZone = $records->groupBy(fn($r) => $r->zone->id);

    foreach ($zoneIds as $zoneId) {
        $zone = $records->firstWhere('zone.id', $zoneId)?->zone ?? $allShifts->firstWhere('zone.id', $zoneId)?->zone;
        if (! $zone) continue;

        $project = $zone->project;
        $zoneShifts = $allShifts->where('zone_id', $zoneId);
        $zoneRecords = $groupedByZone->get($zoneId, collect());

        $html = '<h3 style="text-align: center;">' . $title . ' - ' . ($project->name ?? '-') . '</h3>';
        $html .= '<h4 style="text-align: center;">الموقع: ' . ($zone->name ?? '-') . '</h4>';
        $html .= '<table border="1" cellpadding="2" cellspacing="0">';
        $html .= '<thead><tr>';
        $html .= '<th style="width: 37mm;">الاسم</th>';
        $html .= '<th style="width: 22mm;">رقم الهوية</th>';
        $html .= '<th style="width: 28mm;">الوردية</th>';
        foreach ($dates as $date) {
            $html .= '<th style="width: 6.5mm; font-size:7px; text-align: center;">' . $date->format('d M') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
                foreach ($zoneShifts as $shift) {
            $recordsInShift = $zoneRecords->where('shift.id', $shift->id);
            $pattern = $shift->zone?->pattern;
            $working = (int) ($pattern->working_days ?? 0);
            $off = (int) ($pattern->off_days ?? 0);
            $cycle = $working + $off;
            $shiftStartDate = Carbon::parse($shift->start_date);

            // ✅ عرض الموظفين داخل الوردية
            foreach ($recordsInShift as $record) {
                $employee = $record->employee;

                $html .= '<tr>';
                $html .= '<td style="width: 37mm;">' . $employee->name() . '</td>';
                $html .= '<td style="width: 22mm;">' . $employee->national_id . '</td>';
                $html .= '<td style="width: 28mm;">' . ($shift->name ?? '-') . '</td>';

                foreach ($dates as $target) {
                    $days = $shiftStartDate->diffInDays($target);
                    $inCycle = $days % $cycle;
                    $cycleNum = floor($days / $cycle) + 1;
                    $isWorkDay = $inCycle < $working;

                    $value = 'OFF';
                    if ($isWorkDay) {
                        $value = ($cycleNum % 2 === 1) ? 'M' : 'N';

                        switch ($shift->type) {
                            case 'morning':
                                $value = 'M';
                                break;
                            case 'evening':
                                $value = 'N';
                                break;
                            case 'evening_morning':
                                $value = ($cycleNum % 2 === 1) ? 'N' : 'M';
                                break;
                        }
                    }

                    $bgColor = match ($value) {
                        'M' => '#D9D9D9',
                        'N' => '#999999',
                        'OFF' => '#FFC7CE', // ← بدون لون أحمر
                        default => '#FFFFFF',
                    };

                    $html .= '<td style="width: 6.5mm; background-color:' . $bgColor . '; font-size:7px; text-align: center;">' . $value . '</td>';
                }

                $html .= '</tr>';
            }
                        // ✅ إذا كانت الوردية ناقصة أو بدون أي موظف
            $assignedCount = $recordsInShift->count();
            $missingCount = max(0, $shift->emp_no - $assignedCount);

            if ($missingCount > 0) {
                for ($i = 0; $i < $missingCount; $i++) {
                    $html .= '<tr>';
                    $html .= '<td style="width: 37mm; background-color:#FF0000; color:#FFFFFF;">نقص</td>';
                    $html .= '<td style="width: 22mm; background-color:#FF0000;"></td>';
                    $html .= '<td style="width: 28mm; background-color:#FF0000; color:#FFFFFF;">' . ($shift->name ?? '-') . '</td>';

                    foreach ($dates as $target) {
                        $days = $shiftStartDate->diffInDays($target);
                        $inCycle = $days % $cycle;
                        $cycleNum = floor($days / $cycle) + 1;
                        $isWorkDay = $inCycle < $working;

                        $value = 'OFF';
                        if ($isWorkDay) {
                            $value = ($cycleNum % 2 === 1) ? 'M' : 'N';

                            switch ($shift->type) {
                                case 'morning':
                                    $value = 'M'; break;
                                case 'evening':
                                    $value = 'N'; break;
                                case 'evening_morning':
                                    $value = ($cycleNum % 2 === 1) ? 'N' : 'M'; break;
                            }
                        }

                        $bgColor = match ($value) {
                            'M' => '#D9D9D9',
                            'N' => '#999999',
                            'OFF' => '#FFC7CE',
                            default => '#FFFFFF',
                        };

                        $html .= '<td style="width: 6.5mm; background-color:' . $bgColor . '; font-size:7px; text-align: center;">' . $value . '</td>';
                    }

                    $html .= '</tr>';
                }
            }
        }

        $html .= '</tbody></table><br><br>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    $pdf->Output('employee_work_pattern_report.pdf', 'I');
}







}