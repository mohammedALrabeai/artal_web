<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidSaudiIban implements Rule
{
   public function passes($attribute, $value): bool
    {
        $value = strtoupper(str_replace(' ', '', $value));

        // تحقق من أن الآيبان يبدأ بـ SA
        if (!str_starts_with($value, 'SA')) {
            return false;
        }

        // تحقق أن الطول من 22 إلى 24 خانة (مرونة في عدد الخانات)
        $length = strlen($value);
        if ($length < 22 || $length > 24) {
            return false;
        }

        // تحقق أن الباقي فقط حروف وأرقام
        return preg_match('/^SA\d{2}[A-Z0-9]{' . ($length - 4) . '}$/', $value);
    }

    public function message(): string
    {
        return __('رقم الآيبان غير صالح. يجب أن يبدأ بـ SA ويحتوي على 24 خانة.');
    }
}
