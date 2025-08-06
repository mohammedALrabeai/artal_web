<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Zone;
use App\Models\Shift;
use App\Models\ShiftSlot;
use App\Models\EmployeeProjectRecord;
use App\Forms\Components\EmployeeSelectV2;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Filament\Forms\Components\Actions\Action;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\DB;



class DistributeAssignments extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms, HasPageShield;

    protected static string $view = 'filament.pages.distribute-assignments';

    public ?int $projectId = null;
    protected static ?int $navigationSort = 0;

    public array $slotValues = []; // ← هذا سيحمل كل slot_id => [employee_id, start_date]

    public array $zones = [];

    public function mount(): void
    {
        $this->form->fill();
    }



    protected function getFormSchema(): array
    {
        return [
            Grid::make(12)->schema([
                Select::make('projectId')
                    ->label('اختر المشروع')
                    ->options(Project::pluck('name', 'id'))
                    ->reactive()
                    ->required()
                    ->searchable()
                    ->afterStateUpdated(fn() => $this->zones = [])
                    ->columnSpan(10),

                Placeholder::make('loadZones')
                    ->content('🔄 تحميل المواقع والورديات')
                    ->extraAttributes([
                        'class' => 'filament-button filament-button-size-md rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition',
                        'style' => 'cursor:pointer; text-align:center;',
                        'wire:click' => 'loadZonesFromButton',
                    ])
                    ->visible(fn(callable $get) => $get('projectId'))
                    ->columnSpan(2),
            ]),

            Grid::make(1)->schema(
                collect($this->zones)->map(function ($zone) {
                    return Fieldset::make("📍 {$zone['name']}")->schema(
                        collect($zone['shifts'])->map(function ($shift) {
                            return Fieldset::make("🕒 {$shift['name']}")->schema(
                                collect($shift['slots'])->map(function ($slot, $index) {
                                    $slotId = $slot['id'];
                                    $slotKey = "slot_{$slotId}_employee_id";
                                    $dateKey = "slot_{$slotId}_start_date";
                                    $employeeId = $slot['employee_id'];
                                    $employeeLabel = $slot['employee_label'];

                                    return Grid::make(12)->schema([
                                        Placeholder::make("slot_number_{$slotId}")
                                            ->label('رقم الشاغر')
                                            ->content('رقم الشاغر ' . ($slot['slot_number'] ?? ($index + 1)))
                                            ->columnSpan(2),

                                        EmployeeSelectV2::make("slotValues.{$slotId}.employee_id", false, true)
                                            ->label('الموظف')
                                            ->preload()
                                            ->options(function () use ($employeeId, $employeeLabel) {
                                                return $employeeId && $employeeLabel
                                                    ? [$employeeId => $employeeLabel]
                                                    : [];
                                            })
                                            ->columnSpan(6),

                                        DatePicker::make("slotValues.{$slotId}.start_date")
                                            ->label('تاريخ البداية')
                                            ->required()
                                            ->columnSpan(4),

                                    ]);
                                })->toArray()
                            );
                        })->toArray()
                    );
                })->toArray()
            ),
            Grid::make(1)->schema([
                // ✅ زر حفظ الإسنادات
                \Filament\Forms\Components\Actions::make([
                    Action::make('saveAssignments')
                        ->label('💾 حفظ الإسنادات')
                        ->action('saveAssignments')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد حفظ الإسنادات')
                        ->modalSubheading('هل أنت متأكد أنك تريد حفظ جميع التعديلات؟'),
                ]),
            ])
        ];
    }

    public function updatedZones(): void
    {
        $state = [];

        foreach ($this->zones as $zone) {
            foreach ($zone['shifts'] as $shift) {
                foreach ($shift['slots'] as $slot) {
                    $state["slot_{$slot['id']}_employee_id"] = $slot['employee_id'];
                    $state["slot_{$slot['id']}_start_date"] = now('Asia/Riyadh')->toDateString();
                }
            }
        }

        $this->form->fill($state);
    }

    public function loadZonesFromButton(): void
    {
        if (!$this->projectId) return;

        $this->zones = $this->loadZonesWithShiftsAndSlots($this->projectId);

        foreach ($this->zones as $zone) {
            foreach ($zone['shifts'] as $shift) {
                foreach ($shift['slots'] as $slot) {
                    $this->slotValues[$slot['id']] = [
                        'employee_id' => $slot['employee_id'],
                        'start_date' => $slot['start_date'] ?? now('Asia/Riyadh')->toDateString(), // ✅ استعمل القادم من DB

                    ];
                }
            }
        }

        Notification::make()
            ->title('✅ تم تحميل المواقع والورديات')
            ->success()
            ->send();
    }



    private function loadZonesWithShiftsAndSlots($projectId): array
    {
        return Zone::where('project_id', $projectId)
            ->with(['activeShifts.slots'])
            ->get()
            ->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'shifts' => $zone->activeShifts->map(function ($shift) {
                        return [
                            'id' => $shift->id,
                            'name' => $shift->name,
                            'slots' => $shift->slots->map(function ($slot) {
                                $record = EmployeeProjectRecord::where('shift_slot_id', $slot->id)
                                    ->where('status', 1)
                                    ->whereNull('end_date')
                                    ->with('employee')
                                    ->first();

                                $employee = $record?->employee;

                                return [
                                    'id' => $slot->id,
                                    'employee_id' => $employee?->id,
                                    'employee_label' => $employee
                                        ? "{$employee->first_name} {$employee->father_name} {$employee->family_name} - {$employee->national_id} ({$employee->id})"
                                        : null,
                                    'slot_number' => $slot->slot_number,
                                    'start_date' => $record?->start_date
                                        ? \Illuminate\Support\Carbon::parse($record->start_date)->toDateString()
                                        : null,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray();
    }

    public function saveAssignments(): void
    {
        $created = 0;
        $updated = 0;
        $transferred = 0;
        $notificationJobs = [];

        DB::transaction(function () use (&$created, &$updated, &$transferred, &$notificationJobs) {
            foreach ($this->slotValues as $slotId => $data) {
                $newEmployeeId = $data['employee_id'] ?? null;
                $newStartDate = $data['start_date'] ?? now('Asia/Riyadh')->toDateString();

                $existing = EmployeeProjectRecord::where('shift_slot_id', $slotId)
                    ->where('status', 1)
                    ->whereNull('end_date')
                    ->first();

                $oldEmployeeId = $existing?->employee_id;
                $oldStartDate = $existing?->start_date
    ? \Illuminate\Support\Carbon::parse($existing->start_date)->toDateString()
    : null;

                // 🟠 الحالة 1: لم يتم تغيير شيء (نفس الموظف ونفس التاريخ)
                if ($newEmployeeId && $oldEmployeeId == $newEmployeeId && $oldStartDate == $newStartDate) {
                    $updated++;
                    continue;
                }

                // 🔴 الحالة 2: الشاغر أصبح فارغًا → إنهاء السجل السابق فقط
                if (is_null($newEmployeeId) && $existing) {
                    $existing->update([
                        'status' => 0,
                        'end_date' => now('Asia/Riyadh'),
                    ]);

                    $notificationJobs[] = ['type' => 'end', 'record' => $existing];
                    $transferred++;
                    continue;
                }

                // 🟡 الحالة 3: تغيير التاريخ فقط → تحديث التاريخ فقط بدون إشعار
                if ($newEmployeeId && $oldEmployeeId == $newEmployeeId && $oldStartDate != $newStartDate) {
                    $existing->update([
                        'start_date' => $newStartDate,
                    ]);

                    $updated++;
                    continue;
                }

                // 🔁 الحالة 4: تغيير الموظف → إنهاء القديم وإسناد الجديد
                if ($existing) {
                    $existing->update([
                        'status' => 0,
                        'end_date' => now('Asia/Riyadh'),
                    ]);

                    $notificationJobs[] = ['type' => 'end', 'record' => $existing];
                }

                if ($newEmployeeId) {
                    $slot = ShiftSlot::with('shift.zone')->find($slotId);

                    if ($slot && $slot->shift && $slot->shift->zone) {
                        $newRecord = EmployeeProjectRecord::create([
                            'employee_id' => $newEmployeeId,
                            'shift_slot_id' => $slotId,
                            'start_date' => $newStartDate,
                            'project_id' => $this->projectId,
                            'zone_id' => $slot->shift->zone_id,
                            'shift_id' => $slot->shift_id,
                            'status' => 1,
                            'assigned_by' => auth()->id(), // 👍 من قام بالإسناد
                        ]);

                        $newRecord->employee->update(['status' => 1]);

                        $transferred++;
                        $notificationJobs[] = ['type' => 'assign', 'record' => $newRecord];
                    }
                }
            }
        });

        // \App\Services\AssignmentNotifier::dispatchJobs($notificationJobs);

        Notification::make()
            ->title('✅ تم حفظ الإسنادات الجماعية بنجاح')
            ->body("📌 تم تنفيذ العمليات: {$created} إضافة، {$updated} بدون تغيير، {$transferred} نقل/إحلال")
            ->success()
            ->send();
    }




    public static function getNavigationLabel(): string
    {
        return 'توزيع الموظفين';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }
}
