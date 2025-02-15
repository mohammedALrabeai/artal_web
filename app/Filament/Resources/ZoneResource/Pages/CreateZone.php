<?php

namespace App\Filament\Resources\ZoneResource\Pages;

use App\Filament\Resources\ZoneResource;
use App\Services\NotificationService;
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

    // دالة تُنفذ بعد إنشاء السجل لإرسال الإشعار
    protected function afterCreate(): void
    {
        parent::afterCreate();

        $notificationService = new NotificationService;
        $addedBy = auth()->user()->name; // معرفة من قام بالإضافة
        $zone = $this->record; // بيانات الموقع المضاف

        // الحصول على عدد الموظفين في هذا الموقع (يفترض وجود علاقة employees)
        $employeesCount = $zone->emp_no;
        // الحصول على النطاق المدخل للموقع (يفترض وجود الخاصية range)
        $zoneRange = $zone->area ?? 'غير متوفر';

        // إنشاء رسالة الإشعار بدون عرض الإحداثيات
        $message = "إضافة موقع جديد\n\n";
        $message .= "تمت الإضافة بواسطة: {$addedBy}\n\n";
        $message .= "اسم الموقع: {$zone->name}\n";
        $message .= "النطاق: {$zoneRange}\n";
        $message .= "عدد الموظفين: {$employeesCount}\n";

        // إنشاء رابط خرائط جوجل باستخدام الإحداثيات (يتم استخدامها في الزر فقط)
        $mapsUrl = "https://www.google.com/maps/search/?api=1&query={$zone->lat},{$zone->longg}";

        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'إضافة موقع جديد', // عنوان الإشعار
            $message,
            [
                $notificationService->createAction('عرض الموقع على خرائط جوجل', $mapsUrl, ''),
                // $notificationService->createAction('قائمة المواقع', '/admin/zones', ''),
                $notificationService->createAction('عرض الموقع', "/admin/zones/{$zone->id}", ''),

            ]
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
