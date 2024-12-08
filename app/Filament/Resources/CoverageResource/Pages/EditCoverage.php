<?php

namespace App\Filament\Resources\CoverageResource\Pages;

use App\Filament\Resources\CoverageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoverage extends EditRecord
{
    protected static string $resource = CoverageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
