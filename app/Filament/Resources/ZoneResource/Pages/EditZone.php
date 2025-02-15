<?php

namespace App\Filament\Resources\ZoneResource\Pages;

use App\Filament\Resources\ZoneResource;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZone extends EditRecord
{
    protected static string $resource = ZoneResource::class;

    public $lat;

    public $longg;
    // خاصية لتخزين البيانات الأصلية قبل التعديل
    protected $originalData = [];
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

    // بعد التحديث، نقوم بمقارنة البيانات الأصلية مع البيانات الجديدة وإرسال الإشعار
    // protected function afterUpdate(): void
    // {
    //     parent::afterUpdate();

    //     $notificationService = new NotificationService;
    //     $editedBy = auth()->user()->name;
    //     $zone = $this->record;

    //     // تحديد المتغيرات التي تغيرت مقارنة بالبيانات الأصلية
    //     $changedFields = [];
    //     // استثناء بعض الحقول التي لا نريد عرضها
    //     $ignoredFields = ['updated_at', 'created_at', 'lat', 'longg'];
    //     foreach ($this->originalData as $field => $oldValue) {
    //         if (in_array($field, $ignoredFields)) {
    //             continue;
    //         }
    //         $newValue = $zone->$field;
    //         if ($oldValue != $newValue) {
    //             $changedFields[$field] = [
    //                 'old' => $oldValue,
    //                 'new' => $newValue,
    //             ];
    //         }
    //     }

    //     // بناء نص التغييرات (عرض الحقول التي تغيرت مع القيمة القديمة والجديدة)
    //     $changesMessage = '';
    //     if (! empty($changedFields)) {
    //         $changesMessage .= "التغييرات:\n";
    //         foreach ($changedFields as $field => $values) {
    //             $changesMessage .= "{$field}: {$values['old']} -> {$values['new']}\n";
    //         }
    //     } else {
    //         $changesMessage .= "لم يتم تعديل بيانات محددة.\n";
    //     }

    //     // الحصول على بيانات إضافية للموقع
    //     $zoneRange = $zone->area ?? 'غير متوفر';
    //     $employeesCount = $zone->emp_no;

    //     // إنشاء رسالة الإشعار بدون عرض الإحداثيات كنص
    //     $message = "تعديل موقع\n\n";
    //     $message .= "تم التعديل بواسطة: {$editedBy}\n\n";
    //     $message .= "اسم الموقع: {$zone->name}\n";
    //     $message .= "النطاق: {$zoneRange}\n";
    //     $message .= "عدد الموظفين: {$employeesCount}\n\n";
    //     $message .= $changesMessage;

    //     // إنشاء رابط خرائط جوجل باستخدام الإحداثيات (يُستخدم في الزر فقط)
    //     $mapsUrl = "https://www.google.com/maps/search/?api=1&query={$zone->lat},{$zone->longg}";

    //     $notificationService->sendNotification(
    //         ['manager', 'general_manager', 'hr'],
    //         'تعديل موقع',
    //         $message,
    //         [
    //             $notificationService->createAction('عرض الموقع على خرائط جوجل', $mapsUrl, ''),
    //             $notificationService->createAction('عرض الموقع', "/admin/zones/{$zone->id}", ''),
    //         ]
    //     );
    // }
}
