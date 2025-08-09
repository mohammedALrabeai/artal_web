<?php

namespace App\Filament\Pages;

use App\Models\ManualAttendanceEmployee;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use App\Models\EmployeeProjectRecord;
use Illuminate\Validation\ValidationException;


class ManualAttendancePage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'دفتر الحضور اليدوي';
    protected static string $view = 'filament.pages.manual-attendance-page';

    #[Url(as: 'm', keep: true)]
    public ?string $month = null;

    // حالة الحفظ السريع الافتراضية
    public ?string $defaultStatus = 'M12';

    public array $filtersForGrid = [];

    public function mount(): void
    {
        if (is_null($this->month)) {
            $this->month = now()->startOfMonth()->toDateString();
        }

        // تعبئة الفورم بالقيم الأولية
        $this->form->fill([
            'month' => $this->month,
            'defaultStatus' => $this->defaultStatus,
        ]);

        $this->updateGridFilters(dispatch: false);
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('month')
                ->label('الشهر')
                ->displayFormat('F Y')
                ->reactive(),
            Select::make('defaultStatus')
                ->label('حالة الحفظ السريع')
                ->options([
                    'M12' => 'M12', 'D' => 'D', 'X' => 'X', 'M' => 'M', 'D8' => 'D8', 'A' => 'A',
                    'M8' => 'M8', 'N8' => 'N8', 'N12' => 'N12', 'N' => 'N', 'COV' => 'COV',
                    'OFF' => 'OFF', 'BEFORE' => 'BEFORE', 'AFTER' => 'AFTER', 'PV' => 'PV',
                    'UV' => 'UV', 'SL' => 'SL', 'UL' => 'UL',
                ])
                ->default('M12')
                ->reactive()
                // **تصحيح**: تحديث خاصية الكلاس مباشرة عند تغيير القيمة
                ->afterStateUpdated(function ($state) {
                    $this->defaultStatus = $state;
                }),
        ];
    }

    public function applyFilters(): void
    {
        $this->updateGridFilters(dispatch: true);
    }

    private function updateGridFilters(bool $dispatch = true): void
    {
        // **تصحيح**: التأكد من أن بيانات الفورم هي المصدر الموثوق
        $formData = $this->form->getState();
        $this->month = $formData['month'];
        $this->defaultStatus = $formData['defaultStatus'];

        $this->filtersForGrid = [
            'month' => $this->month,
            'defaultStatus' => $this->defaultStatus,
            'today' => now()->format('Y-m-d'),
        ];

        if ($dispatch) {
            $this->dispatch('filtersApplied', filters: $this->filtersForGrid);
        }
    }

    /**
     * دالة شاملة لحفظ كل تفاصيل الحضور لليوم المحدد.
     */
 
public function saveAttendanceDetails(
    int $manualAttendanceEmployeeId,
    string $date,
    array $details
) {
    // سجل الموظف على صفحة الحضور اليدوي
    $record = ManualAttendanceEmployee::findOrFail($manualAttendanceEmployeeId);

    // جلب الـ zone من employee_project_record_id
    $eprId  = $record->employee_project_record_id;
    $zoneId = optional($record->projectRecord)->zone_id
           ?? optional(EmployeeProjectRecord::find($eprId))->zone_id;

    if (!$zoneId) {
        throw ValidationException::withMessages([
            'actual_zone_id' => 'تعذّر تحديد موقع الموظف (zone) من سجل الإسناد.',
        ]);
    }

    // حفظ/تحديث بناءً على (date + actual_zone_id) ضمن علاقة نفس الموظف
    $record->attendances()->updateOrCreate(
        [
            'date'            => $date,
            'actual_zone_id'  => $zoneId,
        ],
        [
            // القيم المحدّثة
            'status'   => $details['status'],                 // مطلوب
            'notes'    => $details['notes'] ?? null,

            // الجديد
            'is_coverage'                         => $details['has_coverage'] ?? false,
            'replaced_employee_project_record_id' => $details['replaced_record_id'] ?? null,

            // تثبيت المنطقة الفعلية دائمًا من الباك-إند
            'actual_zone_id' => $zoneId,

            // من أنشأ السجل
            'created_by' => auth()->id(),
        ]
    );
}



public function quickSaveStatus($manualAttendanceEmployeeId, $date, $status)
{
    $employee = ManualAttendanceEmployee::findOrFail($manualAttendanceEmployeeId);

    // جلب الـ zone من employee_project_record_id
    $eprId  = $employee->employee_project_record_id;
    $zoneId = optional($employee->projectRecord)->zone_id
           ?? optional(EmployeeProjectRecord::find($eprId))->zone_id;

    if (!$zoneId) {
        throw ValidationException::withMessages([
            'actual_zone_id' => 'تعذّر تحديد موقع الموظف (zone) من سجل الإسناد.',
        ]);
    }

    // الحصول على السجل حسب (date + actual_zone_id)
    $attendance = $employee->attendances()->firstOrNew([
        'date'           => $date,
        'actual_zone_id' => $zoneId,
    ]);

    // تعبئة القيم
    $attendance->status        = $status;        // حاضر/غائب/… حسب ما ترسله
    $attendance->actual_zone_id = $zoneId;       // تثبيت المنطقة الفعلية
    $attendance->created_by    = auth()->id();   // (حسب طلبك: created_by بدل updated_by)

    $attendance->save();

    return [
        'success'    => true,
        'status'     => $status,
        'employeeId' => $manualAttendanceEmployeeId,
        'date'       => $date,
    ];
}

}
