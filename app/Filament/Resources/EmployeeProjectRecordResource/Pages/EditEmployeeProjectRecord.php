<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeProjectRecord extends EditRecord
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
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
