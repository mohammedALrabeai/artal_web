<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EmployeeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // 1. المعرف
            'id'             => $this->id,

            // 2. الاسم الكامل (first, father, grandfather, family)
            'full_name'      => $this->name, 

            // 3. رقم الهوية
            'national_id'    => $this->national_id,

            // 4. رقم الهاتف المحمول ورقم الهاتف الأرضي
            'mobile_number'  => $this->mobile_number,
            // 'phone_number'   => $this->phone_number,

            // 5. رابط الصورة (اختياري)
            // 'avatar_url'     => $this->avatar_path
            //                       ? Storage::url($this->avatar_path)
            //                       : null,

            // 6. اسم آخر مشروع
            'last_project'   => optional($this->latestZone)->name,
        ];
    }
}
