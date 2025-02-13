<?php

namespace App\Filament\Resources\ExclusionResource\Pages;

use App\Filament\Resources\ExclusionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExclusions extends ListRecords
{
    protected static string $resource = ExclusionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
