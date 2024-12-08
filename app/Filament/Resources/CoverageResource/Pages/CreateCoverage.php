<?php

namespace App\Filament\Resources\CoverageResource\Pages;

use App\Filament\Resources\CoverageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCoverage extends CreateRecord
{
    protected static string $resource = CoverageResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = auth()->id();
        return $data;
    }
}
