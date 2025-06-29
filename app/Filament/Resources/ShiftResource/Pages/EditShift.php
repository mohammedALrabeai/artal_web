<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use Filament\Actions;
use App\Models\EmployeeProjectRecord;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ShiftResource;
use Filament\Notifications\Notification;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

   protected function mutateFormDataBeforeSave(array $data): array
    {
        // جلب عدد الموظفين النشطين على هذه الوردية
        $activeCount = EmployeeProjectRecord::where('shift_id', $this->record->id)
            ->where('status', true)
            ->count();

        if ($data['emp_no'] < $activeCount) {
            // إشعار للمستخدم بفشل العملية
            Notification::make()
                ->danger()
                ->title('خطأ في الحفظ')
                ->body("❌ لا يمكنك تقليل عدد الموظفين المطلوبين إلى أقل من عدد الموظفين المسندين فعلياً ({$activeCount}) للوردية.")
                ->send();

            // منع الحفظ فعليًا (إلقاء استثناء)
            throw \Illuminate\Validation\ValidationException::withMessages([
                'emp_no' => "❌ لا يمكنك تقليل العدد إلى أقل من الموظفين المسندين حالياً ({$activeCount})",
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
{
    $shift = $this->record;
    $neededCount = (int) $shift->emp_no;

    // 1. إنشاء السلوتات الناقصة
    $currentCount = \App\Models\ShiftSlot::where('shift_id', $shift->id)->count();
    if ($neededCount > $currentCount) {
        for ($i = $currentCount + 1; $i <= $neededCount; $i++) {
            \App\Models\ShiftSlot::firstOrCreate([
                'shift_id' => $shift->id,
                'slot_number' => $i,
            ]);
        }
    }

    // 2. حذف السلوتات الزائدة (غير المرتبطة)
    if ($neededCount < $currentCount) {
        // أحضر السلوتات الزائدة (التي رقمها أكبر من العدد الجديد)
        $excessSlots = \App\Models\ShiftSlot::where('shift_id', $shift->id)
            ->where('slot_number', '>', $neededCount)
            ->get();

        foreach ($excessSlots as $slot) {
            // تحقق إذا كان هناك موظف نشط على هذا السلوت
            $hasActiveEmployee = \App\Models\EmployeeProjectRecord::where('shift_slot_id', $slot->id)
                ->where('status', true)
                ->whereNull('end_date')
                ->exists();

            // إذا لا يوجد موظف نشط: حذف السلوت
            if (! $hasActiveEmployee) {
                $slot->delete();
            }
            // إذا يوجد موظف نشط: يمكن إظهار إشعار/تحذير (اختياري)
        }
    }
}



}
