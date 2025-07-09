<?php

namespace App\Filament\Resources\PdfTextFieldResource\Pages;

use App\Filament\Resources\PdfTextFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePdfTextField extends CreateRecord
{
    protected static string $resource = PdfTextFieldResource::class;
      protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
