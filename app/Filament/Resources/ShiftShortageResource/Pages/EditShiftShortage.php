<?php

namespace App\Filament\Resources\ShiftShortageResource\Pages;

use App\Filament\Resources\ShiftShortageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShiftShortage extends EditRecord
{
    protected static string $resource = ShiftShortageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
