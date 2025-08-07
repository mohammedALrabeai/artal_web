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

    $record->attendances()->updateOrCreate(
        ['date' => $date],
        [
            'status'  => $details['status'],
            'notes'   => $details['notes'] ?? null,

            // ✔︎ الجديد
            'is_coverage'                       => $details['has_coverage'] ?? false,
            'replaced_employee_project_record_id' => $details['replaced_record_id'] ?? null,

            // ✔︎ سجّل مَن أنشأ
            'created_by' => auth()->id(),
        ]
    );
}


    /**
     * دالة جديدة للحفظ السريع باستخدام الحالة الافتراضية
     */
  public function quickSaveStatus($employeeId, $date, $status)
{
    $employee   = ManualAttendanceEmployee::findOrFail($employeeId);
    $attendance = $employee->attendances()->firstOrNew(['date' => $date]);

    $attendance->status     = $status;
    $attendance->created_by = auth()->id();   // ← بدل updated_by
    $attendance->save();

    return [
        'success'    => true,
        'status'     => $status,
        'employeeId' => $employeeId,
        'date'       => $date,
    ];
}

}
