<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use Filament\Pages\Page;
use App\Models\EmployeeCoordinate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Models\Zone; // تأكد من وجود موديل المنطقة

class EmployeePaths extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $title = '>>';
    protected static string $view = 'filament.pages.employee-paths';

    public string $employeeId;
    public string $employeeName;

    public function mount(string $employeeId): void
    {
        $this->employeeId = $employeeId;
              // جلب اسم الموظف
              $employee = Employee::find($employeeId, ['first_name', 'family_name']);
              $this->employeeName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'غير معروف';
              $locale = session('locale', 'ar'); // افتراضيًا الإنجليزية إذا لم تكن اللغة محددة
              App::setLocale($locale);
    }

    public function getEmployeeRoute($date = null)
    {
        $date = $date ?? now()->toDateString();

        // جلب بيانات المسار
        $coordinates = EmployeeCoordinate::where('employee_id', $this->employeeId)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp', 'asc')
            ->get(['latitude', 'longitude', 'timestamp']);

        // جلب بيانات المنطقة (zone) إذا كانت موجودة
        $zone = Zone::where('employee_id', $this->employeeId)->first(['latitude', 'longitude', 'area']);

        return [
            'route' => $coordinates,
            'zone' => $zone
        ];
    }

    protected function getViewData(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'employeeName' => $this->employeeName,
            'currentDate' => now()->toDateString(),
        ];
    }
}
