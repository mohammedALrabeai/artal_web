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
    protected static ?string $title = 'دفتر الحضور اليدوي';
    protected static string $view = 'filament.pages.manual-attendance-page';

    // هذه الخصائص ستبقى لتخزين قيم الفلاتر من النموذج
    public ?int $projectId = null;
    public ?int $zoneId = null;
    public ?int $shiftId = null;
    public ?string $month = null;

    /**
     * ✅ [جديد] خاصية لتخزين الفلاتر التي تم "تطبيقها" فعلياً.
     * AG Grid سيقرأ دائماً من هذه الخاصية لضمان استخدام البيانات الصحيحة.
     */
    public array $filtersForGrid = [];

    /**
     * يتم تنفيذها عند تحميل الصفحة لأول مرة.
     */
    public function mount(): void
    {
        // تعيين الشهر الحالي كقيمة افتراضية للنموذج
        $this->month = now()->startOfMonth()->toDateString();
        
        // تعبئة النموذج بالقيم الافتراضية
        $this->form->fill([
            'month' => $this->month,
        ]);

        // ✅ [جديد] تعيين الفلاتر الأولية للشبكة عند تحميل الصفحة
        $this->updateGridFilters();
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
                ->default(now()->startOfMonth())
                ->reactive(), // reactive ضرورية لربط القيمة مع خاصية $month
        ];
    }

    /**
     * ✅ [جديد] دالة يتم استدعاؤها عند الضغط على زر "تطبيق الفلاتر".
     */
    public function applyFilters(): void
    {
        // كل ما تفعله هو تحديث الفلاتر وإرسال الحدث إلى الواجهة الأمامية
        $this->updateGridFilters();
    }

    /**
     * ✅ [جديد] دالة مساعدة لتحديث الفلاتر وإعلام الواجهة الأمامية.
     */
    private function updateGridFilters(): void
    {
        // 1. تحديث الخاصية التي سيستخدمها AG Grid
        $this->filtersForGrid = [
            'projectId' => $this->projectId,
            'zoneId'    => $this->zoneId,
            'shiftId'   => $this->shiftId,
            'month'     => $this->month,
            'today'     => now()->format('Y-m-d'),
        ];

        // 2. إرسال حدث (event) إلى JavaScript مع الفلاتر الجديدة
        $this->dispatch('filtersApplied', filters: $this->filtersForGrid);
    }

    /**
     * [محذوف] لم نعد بحاجة لهذه الدالة لأننا نستخدم خاصية $filtersForGrid
     * public function getFilterData(): array { ... }
     */

    public function saveStatus($employeeId, $date, $status)
    {
        $employee = ManualAttendanceEmployee::findOrFail($employeeId);

        $attendance = $employee->attendances()->firstOrNew(['date' => $date]);
        $attendance->status = $status;
        $attendance->updated_by = auth()->id();
        $attendance->save();
    }
}
