<?php

// namespace App\Filament\Resources\AttendanceResource\Pages;
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Employee;
use App\Models\Attendance;

class EmployeeAttendanceReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-report';
    protected static ?string $navigationLabel = 'Employee Attendance Report';
    protected static string $view = 'filament.pages.employee-attendance-report';

    public $employeeId;

    public function mount($employeeId)
    {
        $this->employeeId = $employeeId;
    }

    public function getAttendanceRecords()
    {
        return Attendance::where('employee_id', $this->employeeId)->get();
    }
}
