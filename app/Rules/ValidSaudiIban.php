<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidSaudiIban implements Rule
{
    public function passes($attribute, $value): bool
    {
        // إزالة المسافات وتحويل إلى أحرف كبيرة
        $value = strtoupper(str_replace(' ', '', $value));

        // تحقق من التنسيق العام للآيبان السعودي
        return preg_match('/^SA\d{22}$/', $value);
    }

    public function message(): string
    {
        return __('رقم الآيبان غير صالح. يجب أن يبدأ بـ SA ويحتوي على 24 خانة.');
    }
}
