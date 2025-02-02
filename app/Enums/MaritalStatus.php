<?php
namespace App\Enums;

enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    public static function fromArabic(string $value): ?self
    {
        return match ($value) {
            'أعزب' => self::SINGLE,
            'متزوج' => self::MARRIED,
            'مطلق' => self::DIVORCED,
            'أرمل' => self::WIDOWED,
            default => null, // تجنب الخطأ إذا كانت القيمة غير متوقعة
        };
    }

    public function label(): string
    {
        return match($this) {
            self::SINGLE => __('marital_status.single'),
            self::MARRIED => __('marital_status.married'),
            self::DIVORCED => __('marital_status.divorced'),
            self::WIDOWED => __('marital_status.widowed'),
        };
    }
}
