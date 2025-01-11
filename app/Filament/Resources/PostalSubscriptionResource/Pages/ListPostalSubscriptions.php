<?php

namespace App\Filament\Resources\PostalSubscriptionResource\Pages;

use App\Filament\Resources\PostalSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPostalSubscriptions extends ListRecords
{
    protected static string $resource = PostalSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
