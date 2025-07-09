<?php

namespace App\Filament\Resources\PdfTextFieldResource\Pages;

use App\Filament\Resources\PdfTextFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPdfTextField extends EditRecord
{
    protected static string $resource = PdfTextFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
