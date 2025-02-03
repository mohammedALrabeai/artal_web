<?php

namespace App\Filament\Resources\ZoneResource\Pages;

use App\Filament\Resources\ZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateZone extends CreateRecord
{
    protected static string $resource = ZoneResource::class;
     // تعريف المتغيرات دون قيمة افتراضية، بحيث يختار المستخدم الموقع
     public $lat;
     public $longg;
 
     // استخدم onMounted بدلاً من mount() لتجنب تعارض التوقيعات:
     protected function onMounted(): void
     {
         // ترك المتغيرات فارغة (أو يمكنك تهيئتها بسلسلة فارغة)
         $this->lat = '';
         $this->longg = '';
     }
 
     // استقبال الحدث من JavaScript لتحديث الإحداثيات
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



    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
