<?php

namespace App\Filament\Resources\PatternResource\Pages;


use App\Filament\Resources\PatternResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePattern extends CreateRecord
{
    protected static string $resource = PatternResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
