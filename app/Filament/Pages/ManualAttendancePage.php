<?php

namespace App\Filament\Pages;

use App\Models\Zone;
use App\Models\Shift;
use App\Models\Project;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use App\Models\ManualAttendanceEmployee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManualAttendancePage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'دفتر الحضور اليدوي (سريع)';
    protected static string $view = 'filament.pages.manual-attendance-page';

    // هذه الخصائص ستبقى لتخزين قيم الفلاتر
    public ?int $projectId = null;
    public ?int $zoneId = null;
    public ?int $shiftId = null;
    public ?string $month = null;

    /**
     * يتم تنفيذها عند تحميل الصفحة لأول مرة.
     */
    public function mount(): void
    {
        // تعيين الشهر الحالي كقيمة افتراضية
        $this->month = now()->startOfMonth()->toDateString();
    }

    /**
     * مخطط نموذج الفلاتر في أعلى الصفحة.
     */
    protected function getFormSchema(): array
    {
        return [
            Select::make('projectId')
                ->label('المشروع')
                ->options(Project::pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(fn () => $this->zoneId = null), // إعادة تعيين الموقع عند تغيير المشروع

            Select::make('zoneId')
                ->label('الموقع')
                ->options(fn () => $this->projectId
                    ? Zone::where('project_id', $this->projectId)->pluck('name', 'id')
                    : [])
                ->reactive()
                ->afterStateUpdated(fn () => $this->shiftId = null), // إعادة تعيين الوردية عند تغيير الموقع

            Select::make('shiftId')
                ->label('الوردية')
                ->options(fn () => $this->zoneId
                    ? Shift::where('zone_id', $this->zoneId)->pluck('name', 'id')
                    : []),

            DatePicker::make('month')
                ->label('الشهر')
                ->displayFormat('F Y') // عرض اسم الشهر والسنة
                ->reactive()
                ->maxDate(now()->endOfMonth())
                ->default(now()->startOfMonth()),
        ];
    }

    /**
     * هذه هي الدالة العامة التي يستدعيها كود JavaScript
     * للحصول على قيم الفلاتر الحالية بشكل آمن.
     */
    public function getFilterData(): array
    {
        return [
            'projectId' => $this->projectId,
            'zoneId' => $this->zoneId,
            'shiftId' => $this->shiftId,
            'month' => $this->month,
            'today' => now()->format('Y-m-d'),
        ];
    }

       public function saveStatus($employeeId, $date, $status)
    {
        $employee = ManualAttendanceEmployee::findOrFail($employeeId);

        $attendance = $employee->attendances()->firstOrNew(['date' => $date]);
        $attendance->status = $status;
        $attendance->updated_by = auth()->id();
        $attendance->save();

        // لا نحتاج لإعادة تحميل أي شيء لأن الواجهة الأمامية ستتولى التحديث
    }
}
