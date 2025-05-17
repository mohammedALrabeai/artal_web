<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Widgets\Widget;

class AttendanceStatsWidget extends Widget implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.attendance-stats-widget';

    protected static ?int $sort = 0;

    public ?string $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString(); // التاريخ الافتراضي اليوم
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('selectedDate')
                ->label('اختر التاريخ')
                ->default(now())
                ->closeOnDateSelection()
                ->reactive()
                ->afterStateUpdated(fn () => $this->dispatch('$refresh')),
        ];
    }

    protected function getData(): array
    {
        $today = $this->selectedDate ?? now()->toDateString();

        $totalEmployees = Employee::where('status', 1)->count();

        $present = Attendance::whereDate('date', $today)
            ->where('status', 'present')
            ->distinct('employee_id')
            ->count('employee_id');

        $coverage = Attendance::whereDate('date', $today)
            ->where('status', 'coverage')
            ->distinct('employee_id')
            ->count('employee_id');

        $offStatuses = ['off', 'leave', 'UV', 'M'];

        $off = Attendance::whereDate('date', $today)
            ->whereIn('status', $offStatuses)
            ->distinct('employee_id')
            ->count('employee_id');

        $absent = Attendance::whereDate('date', $today)
            ->where('status', 'absent')
            ->distinct('employee_id')
            ->count('employee_id');

        return [
            'total' => $totalEmployees,
            'present' => $present,
            'coverage' => $coverage,
            'off' => $off,
            'absent' => $absent,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }
}
