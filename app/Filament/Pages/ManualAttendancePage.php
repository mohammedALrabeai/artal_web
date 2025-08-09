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
    $record = ManualAttendanceEmployee::findOrFail($manualAttendanceEmployeeId);

    // لم نعد نستخدم EPR هنا؛ نعتمد actual_zone_id المربوط بالسجل الشهري نفسه
    $zoneId = $record->actual_zone_id;
    if (! $zoneId) {
        throw ValidationException::withMessages([
            'actual_zone_id' => 'تعذّر تحديد موقع السجل الشهري.',
        ]);
    }

    // المفتاح الوحيد هو التاريخ — لأن manual_attendance_employee_id يأتي من العلاقة
    $record->attendances()->updateOrCreate(
        ['date' => $date],
        [
            'status'   => $details['status'],
            'notes'    => $details['notes'] ?? null,

            'is_coverage'                         => $details['has_coverage'] ?? false,
            'replaced_employee_project_record_id' => $details['replaced_record_id'] ?? null,

            // لا نمرّر actual_zone_id هنا لأنه ليس عمودًا في manual_attendances
            'created_by' => auth()->id(),
        ]
    );
}




public function quickSaveStatus($manualAttendanceEmployeeId, $date, $status)
{
    $employee = ManualAttendanceEmployee::findOrFail($manualAttendanceEmployeeId);

    // نعتمد الموقع من السجل الشهري نفسه
    $zoneId = $employee->actual_zone_id;
    if (! $zoneId) {
        throw ValidationException::withMessages([
            'actual_zone_id' => 'تعذّر تحديد موقع السجل الشهري.',
        ]);
    }

    // المفتاح هو التاريخ فقط (العلاقة تضيف manual_attendance_employee_id)
    $attendance = $employee->attendances()->firstOrNew([
        'date' => $date,
    ]);

    $attendance->status      = $status;
    // لا يوجد actual_zone_id في manual_attendances
    $attendance->created_by  = auth()->id();

    $attendance->save();

    return [
        'success'    => true,
        'status'     => $status,
        'employeeId' => $manualAttendanceEmployeeId,
        'date'       => $date,
    ];
}


}
