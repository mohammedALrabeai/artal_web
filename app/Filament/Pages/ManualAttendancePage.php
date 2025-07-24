<?php

// app/Filament/Pages/ManualAttendancePage.php

namespace App\Filament\Pages;

use App\Models\ManualAttendanceEmployee;
use App\Models\Project;
use App\Models\Zone;
use App\Models\Shift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManualAttendancePage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;


    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'دفتر الحضور اليدوي';
    protected static string $view = 'filament.pages.manual-attendance-page';

    public ?int $projectId = null;
    public ?int $zoneId = null;
    public ?int $shiftId = null;
    public ?string $month = null;

    public ?int $editingEmployeeId = null;
public ?string $editingDate = null;
public ?String $editableDate = null;

public function editCell($employeeId, $date)
{
    $this->editingEmployeeId = $employeeId;
    $this->editingDate = $date;
}



    public function mount(): void
    {
        $this->month = now()->startOfMonth()->toDateString();
            // $this->editableDate = "2025-06-24"; // أو أي تاريخ ثابت مثل: '2025-07-24'
             $this->editableDate = now()->toDateString();

    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('projectId')
                ->label('المشروع')
                ->options(Project::pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(fn () => $this->zoneId = null),

            Select::make('zoneId')
                ->label('الموقع')
                ->options(fn () => $this->projectId
                    ? Zone::where('project_id', $this->projectId)->pluck('name', 'id')
                    : [])
                ->reactive()
                ->afterStateUpdated(fn () => $this->shiftId = null),

            Select::make('shiftId')
                ->label('الوردية')
                ->options(fn () => $this->zoneId
                    ? Shift::where('zone_id', $this->zoneId)->pluck('name', 'id')
                    : []),

            DatePicker::make('month')
                ->label('الشهر')
                ->displayFormat('F Y')
                ->reactive()
                ->maxDate(now()->endOfMonth())
                ->default(now()->startOfMonth()),
        ];
    }


    public function getAttendanceMatrixProperty()
{
    return \App\Models\ManualAttendance::query()
        ->whereIn('manual_attendance_employee_id', $this->employees->pluck('id'))
        ->get()
        ->groupBy(fn ($row) => $row->manual_attendance_employee_id . '|' . $row->date);
}

  public function getEmployeesProperty()
{
    if (! $this->month) {
        return collect();
    }

    $query = ManualAttendanceEmployee::with('projectRecord.employee');

    $query->where('attendance_month', Carbon::parse($this->month)->startOfMonth()->toDateString());

    if ($this->projectId) {
        $query->whereHas('projectRecord', fn ($q) =>
            $q->where('project_id', $this->projectId)
        );
    }

    if ($this->zoneId) {
        $query->whereHas('projectRecord', fn ($q) =>
            $q->where('zone_id', $this->zoneId)
        );
    }

    if ($this->shiftId) {
        $query->whereHas('projectRecord', fn ($q) =>
            $q->where('shift_id', $this->shiftId)
        );
    }

    return $query->get();
}


    public function getDaysProperty(): array
    {
        $start = Carbon::parse($this->month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return collect()
            ->range(1, $end->day)
            ->map(fn ($day) => $start->copy()->day($day)->format('Y-m-d'))
            ->toArray();
    }


    public function getWorkPatternLabel($record, $date): string
{
    if (! $record->shift || ! $record->shift->zone || ! $record->shift->zone->pattern) {
        return '❌';
    }

    $pattern = $record->shift->zone->pattern;
    $workingDays = (int) $pattern->working_days;
    $offDays = (int) $pattern->off_days;
    $cycleLength = $workingDays + $offDays;

    $startDate = \Carbon\Carbon::parse($record->shift->start_date);
    $targetDate = \Carbon\Carbon::parse($date);
    $totalDays = $startDate->diffInDays($targetDate);
    $currentDayInCycle = $totalDays % $cycleLength;
    $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

    $isWorkDay = $currentDayInCycle < $workingDays;

    if (! $isWorkDay) {
        return 'OFF';
    }

    $type = $record->shift->type;

    return match ($type) {
        'morning' => 'M',
        'evening' => 'N',
        'morning_evening' => ($cycleNumber % 2 == 1 ? 'M' : 'N'),
        'evening_morning' => ($cycleNumber % 2 == 1 ? 'N' : 'M'),
        default => 'M',
    };
}

public function saveStatus($employeeId, $date, $status)
{
    $employee = ManualAttendanceEmployee::findOrFail($employeeId);

    $attendance = $employee->attendances()->firstOrNew([
        'date' => $date,
    ]);

    $attendance->status = $status;
    $attendance->updated_by = auth()->id();
    $attendance->save();

    $this->editingEmployeeId = null;
    $this->editingDate = null;

    $this->dispatch('$refresh'); // ✅ الحل الأنسب
}



}
