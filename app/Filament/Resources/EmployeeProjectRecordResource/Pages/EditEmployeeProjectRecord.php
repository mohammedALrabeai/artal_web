<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException; 

class EditEmployeeProjectRecord extends EditRecord
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


     protected function afterSave(): void
{
    // dd($this->record->wasChanged('status'),$this->record);
    // تغيّر من مُفعَّل → مُعطَّل؟
    if ($this->record->wasChanged('status')
        && intval($this->record->getOriginal('status')) === 1
        && intval($this->record->status) === 0) {

        try {
            $employee = $this->record->employee;
            $project  = $this->record->project;

            if ($employee && $employee->mobile_number
                && $project && $project->has_whatsapp_group && $project->whatsapp_group_id) {

                $clean = preg_replace('/[^0-9]/', '', $employee->mobile_number);
                (new \App\Services\WhatsApp\WhatsAppGroupService)
                    ->removeParticipant($project->whatsapp_group_id, $clean);
            }
        } catch (\Throwable $e) {
            \Log::warning('فشل إزالة الموظف من جروب واتساب بعد تعطيل الإسناد', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}


// protected function beforeSave(): void
// {
//     $record = $this->getRecord();
//     $newData = $this->form->getState();
//     dd($newData, $record);

//     if (
//         $record->status !== $newData['status'] && // تم تغيير الحالة
//         $newData['status'] === false &&           // أصبحت غير نشطة
//         empty($newData['end_date'])               // ولا يوجد end_date
//     ) {
//         $record->end_date = now('Asia/Riyadh');
//     }
// }






}
