<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Project;
use App\Models\Zone;
use App\Models\Shift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManualAttendancePage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'دفتر الحضور اليدوي (نسخة سريعة)';
    protected static string $view = 'filament.pages.manual-attendance-page';

    // هذه الخصائص ستبقى لتخزين قيم الفلاتر
    public ?int $projectId = null;
    public ?int $zoneId = null;
    public ?int $shiftId = null;
    public ?string $month = null;

    public function mount(): void
    {
        $this->month = now()->startOfMonth()->toDateString();
    }

    // هذا النموذج يبقى كما هو للتحكم بالفلاتر
    protected function getFormSchema(): array
    {
        return [
            Select::make('projectId')
                ->label('المشروع')
                ->options(Project::pluck('name', 'id'))
                ->reactive(),

            Select::make('zoneId')
                ->label('الموقع')
                ->options(fn () => $this->projectId
                    ? Zone::where('project_id', $this->projectId)->pluck('name', 'id')
                    : [])
                ->reactive(),

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

    // دالة مساعدة لإرسال بيانات الفلاتر إلى الواجهة الأمامية
    protected function getFilterData(): array
    {
        return [
            'projectId' => $this->projectId,
            'zoneId' => $this->zoneId,
            'shiftId' => $this->shiftId,
            'month' => $this->month,
        ];
    }
}
