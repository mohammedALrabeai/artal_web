<?php

namespace App\Filament\Pages;

use App\Models\ManualAttendanceEmployee;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Zone;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ManualAttendancePage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'دفتر الحضور اليدوي';
    protected static string $view = 'filament.pages.manual-attendance-page';

    #[Url(as: 'p', keep: true)]
    public ?int $projectId = null;

    #[Url(as: 'z', keep: true)]
    public ?int $zoneId = null;

    #[Url(as: 's', keep: true)]
    public ?int $shiftId = null;

    #[Url(as: 'm', keep: true)]
    public ?string $month = null;

    public array $filtersForGrid = [];

    public function mount(): void
    {
        if (is_null($this->month)) {
            $this->month = now()->startOfMonth()->toDateString();
        }

        $this->form->fill([
            'projectId' => $this->projectId,
            'zoneId' => $this->zoneId,
            'shiftId' => $this->shiftId,
            'month' => $this->month,
        ]);

        $this->updateGridFilters(dispatch: false);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('projectId')
                ->label('المشروع')
                ->options(Project::pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->zoneId = null;
                    $this->shiftId = null;
                    $this->form->fill(['zoneId' => null, 'shiftId' => null]);
                }),

            Select::make('zoneId')
                ->label('الموقع')
                ->options(fn () => $this->projectId
                    ? Zone::where('project_id', $this->projectId)->pluck('name', 'id')
                    : [])
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->shiftId = null;
                    $this->form->fill(['shiftId' => null]);
                }),

            Select::make('shiftId')
                ->label('الوردية')
                ->options(fn () => $this->zoneId
                    ? Shift::where('zone_id', $this->zoneId)->pluck('name', 'id')
                    : [])
                ->reactive(),

            DatePicker::make('month')
                ->label('الشهر')
                ->displayFormat('F Y')
                ->reactive(),
        ];
    }

    public function applyFilters(): void
    {
        $this->updateGridFilters(dispatch: true);
    }

    private function updateGridFilters(bool $dispatch = true): void
    {
        $this->filtersForGrid = [
            'projectId' => $this->projectId,
            'zoneId' => $this->zoneId,
            'shiftId' => $this->shiftId,
            'month' => $this->month,
            'today' => now()->format('Y-m-d'),
        ];

        if ($dispatch) {
            $this->dispatch('filtersApplied', filters: $this->filtersForGrid);
        }
    }

    public function saveStatus($employeeId, $date, $status)
    {
        $employee = ManualAttendanceEmployee::findOrFail($employeeId);
        $attendance = $employee->attendances()->firstOrNew(['date' => $date]);
        $attendance->status = $status;
        $attendance->updated_by = auth()->id();
        $attendance->save();
    }

    public function saveCoverage($employeeId, $date, $covValue)
    {
        $employee = ManualAttendanceEmployee::findOrFail($employeeId);
        $attendance = $employee->attendances()->firstOrNew(['date' => $date]);

        if (! $attendance->exists) {
            $attendance->status = '';
        }

        $attendance->has_coverage_shift = ($covValue === 'COV');
        $attendance->updated_by = auth()->id();
        $attendance->save();
    }
}
