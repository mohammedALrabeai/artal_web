<?php

namespace App\Filament\Resources\ZoneResource\Pages;

use App\Filament\Resources\ZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZone extends EditRecord
{
    protected static string $resource = ZoneResource::class;
    public $lat;
    public $longg;

    // عند ملء النموذج ببيانات السجل، نستقبل الإحداثيات المُخزنة
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->lat = $data['lat'] ?? '';
        $this->longg = $data['longg'] ?? '';
        return $data;
    }

    public function updateLatLng($lat, $longg)
    {
        $this->lat = $lat;
        $this->longg = $longg;
    }

    protected function getListeners(): array
    {
        return [
            'updateLatLng' => 'updateLatLng',
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
