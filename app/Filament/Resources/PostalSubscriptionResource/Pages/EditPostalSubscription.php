<?php

namespace App\Filament\Resources\PostalSubscriptionResource\Pages;

use App\Filament\Resources\PostalSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPostalSubscription extends EditRecord
{
    protected static string $resource = PostalSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
