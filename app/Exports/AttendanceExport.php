<?php
namespace App\Exports;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AttendanceExport implements FromView
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
}
