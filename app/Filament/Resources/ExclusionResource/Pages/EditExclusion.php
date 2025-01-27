<?php

namespace App\Filament\Resources\ExclusionResource\Pages;

use App\Filament\Resources\ExclusionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExclusion extends EditRecord
{
    protected static string $resource = ExclusionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
