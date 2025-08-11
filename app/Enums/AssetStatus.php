<?php

namespace App\Enums;

enum AssetStatus: string
{
    case AVAILABLE  = 'available';   // متاح للتسليم
    case MAINTENANCE = 'maintenance';// صيانة (غير متاح)
    case LOST       = 'lost';        // مفقود (غير متاح)
    case DAMAGED    = 'damaged';     // تالف (غير متاح)
    case CHARGED    = 'charged';     // خُصِم من الموظف / تم التعويض (غير متاح)
    case RETIRED    = 'retired';     // خارج الخدمة (غير متاح)

    public function isAssignable(): bool
    {
        return match ($this) {
            self::AVAILABLE => true,
            default => false,
        };
    }

    public static function labels(): array
    {
        return [
            self::AVAILABLE->value   => 'Available',
            self::MAINTENANCE->value => 'Maintenance',
            self::LOST->value        => 'Lost',
            self::DAMAGED->value     => 'Damaged',
            self::CHARGED->value     => 'Charged',
            self::RETIRED->value     => 'Retired',
        ];
    }
}
