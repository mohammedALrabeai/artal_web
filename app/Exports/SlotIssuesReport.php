<?php 

// app/Exports/SlotIssuesReport.php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SlotIssuesReport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'بدون شاغر'       => new UnassignedEmployeesExport,
            'شاغر مكرر'       => new DuplicateSlotAssignmentsExport,
            'شاغر لا يتبع وردية' => new MismatchedSlotAssignmentsExport,
             'نقص شواغر الوردية'  => new ShiftsMissingSlotsExport, 
        ];
    }
}
